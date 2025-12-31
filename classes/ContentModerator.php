<?php
/**
 * AI Content Moderation System for Basehit.io
 * Uses Perplexity API for intelligent content analysis
 */
require_once __DIR__ . '/RateLimiter.php';

class ContentModerator {
    private $pdo;
    private $perplexityApiKey;
    private $apiUrl = 'https://api.perplexity.ai/chat/completions';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Load API key from config
        $config = require __DIR__ . '/../config/moderation.php';
        $this->perplexityApiKey = $config['api_key'] ?? '';
    }

    public function moderateText($content, $contentType, $contentId, $userId) {
        try {
            // Check Rate Limit
            $rateLimiter = new RateLimiter($this->pdo);
            $identifier = $userId ?: RateLimiter::getIdentifier();
            
            // Get settings for rate limit
            $stmt = $this->pdo->query("SELECT setting_value FROM moderation_settings WHERE setting_key = 'ai_rate_limit_per_hour'");
            $limit = $stmt->fetchColumn() ?: 10;
            
            $limitCheck = $rateLimiter->checkLimit($identifier, 'ai_moderation', $limit, 3600);
            
            if (!$limitCheck['allowed']) {
                return [
                    'approved' => true, 
                    'risk_level' => 'rate_limited', 
                    'message' => 'Rate limit exceeded. Please try again in ' . ceil($limitCheck['retry_after'] / 60) . ' minutes.'
                ];
            }

            $prompt = $this->buildModerationPrompt($content, $contentType);
            $response = $this->callPerplexityAPI($prompt);
            $analysis = $this->parseAIResponse($response);
            $this->logModerationResult($contentType, $contentId, $userId, $content, $analysis);

            if ($analysis['risk_level'] === 'high') {
                $this->flagContent($contentType, $contentId, $analysis);
            }

            return $analysis;
        } catch (Exception $e) {
            error_log("Content Moderation Error: " . $e->getMessage());
            return ['approved' => true, 'risk_level' => 'error', 'message' => 'Moderation service unavailable'];
        }
    }

    private function buildModerationPrompt($content, $contentType) {
        return "You are a content moderation AI for a personals/dating platform called Basehit.io. 

Analyze the following {$contentType} content and determine if it violates community guidelines.

CONTENT TO ANALYZE:
{$content}

MODERATION CRITERIA:
- Explicit sexual content (detailed descriptions, solicitation)
- Harassment, threats, or hate speech
- Personal information (phone numbers, addresses, explicit contact details)
- Spam or commercial solicitation
- Illegal activities
- Minors or age-inappropriate content

RESPONSE FORMAT (JSON only):
{
  \"approved\": true/false,
  \"risk_level\": \"low/medium/high\",
  \"violations\": [\"violation1\", \"violation2\"],
  \"reason\": \"brief explanation\",
  \"confidence\": 0.0-1.0,
  \"suggested_action\": \"approve/flag/reject\"
}

Respond ONLY with valid JSON. Be permissive for adult dating content but strict on illegal/harmful content.";
    }

    private function callPerplexityAPI($prompt) {
        $ch = curl_init($this->apiUrl);

        $data = [
            'model' => 'sonar-pro',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a content moderation expert. Respond only with valid JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.2,
            'max_tokens' => 500
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->perplexityApiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('API Request Error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('API returned status ' . $httpCode);
        }

        return json_decode($response, true);
    }

    private function parseAIResponse($response) {
        try {
            $content = $response['choices'][0]['message']['content'] ?? '';
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $analysis = json_decode($jsonStr, true);

                if ($analysis) {
                    return [
                        'approved' => $analysis['approved'] ?? true,
                        'risk_level' => $analysis['risk_level'] ?? 'low',
                        'violations' => $analysis['violations'] ?? [],
                        'reason' => $analysis['reason'] ?? '',
                        'confidence' => $analysis['confidence'] ?? 0.0,
                        'suggested_action' => $analysis['suggested_action'] ?? 'approve'
                    ];
                }
            }

            return ['approved' => true, 'risk_level' => 'low', 'violations' => [], 'reason' => 'Unable to parse AI response', 'confidence' => 0.0, 'suggested_action' => 'approve'];
        } catch (Exception $e) {
            error_log("Parse Error: " . $e->getMessage());
            return ['approved' => true, 'risk_level' => 'error', 'violations' => [], 'reason' => 'Parse error', 'confidence' => 0.0, 'suggested_action' => 'approve'];
        }
    }

    private function logModerationResult($contentType, $contentId, $userId, $content, $analysis) {
        $query = "INSERT INTO moderation_logs (content_type, content_id, user_id, content, risk_level, violations, reason, confidence, suggested_action, created_at) 
                  VALUES (:content_type, :content_id, :user_id, :content, :risk_level, :violations, :reason, :confidence, :suggested_action, NOW())";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'content_type' => $contentType,
            'content_id' => $contentId,
            'user_id' => $userId,
            'content' => substr($content, 0, 5000),
            'risk_level' => $analysis['risk_level'],
            'violations' => json_encode($analysis['violations']),
            'reason' => $analysis['reason'],
            'confidence' => $analysis['confidence'],
            'suggested_action' => $analysis['suggested_action']
        ]);
    }

    private function flagContent($contentType, $contentId, $analysis) {
        $tables = [
            'listing' => 'listings',
            'marketplace_listing' => 'creator_listings',
            'forum_post' => 'forum_posts',
            'forum_thread' => 'forum_threads',
            'profile' => 'users'
        ];

        if (isset($tables[$contentType])) {
            $table = $tables[$contentType];
            $query = "UPDATE {$table} SET status = 'flagged', moderation_status = 'pending' WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['id' => $contentId]);
        }
    }
}

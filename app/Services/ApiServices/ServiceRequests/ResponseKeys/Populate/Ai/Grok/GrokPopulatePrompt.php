<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Ai\Grok;

use App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Ai\AiPopulatePrompt;
use InvalidArgumentException;

class GrokPopulatePrompt extends AiPopulatePrompt
{

    public function prompt(array $apiResponse, array $serviceResponseKeys): string
    {
        // Validate input
        if (empty($apiResponse)) {
            throw new InvalidArgumentException('api_response cannot be empty');
        }

        if (!is_array($apiResponse)) {
            throw new InvalidArgumentException('api_response must be an array');
        }

        if (!is_array($serviceResponseKeys)) {
            throw new InvalidArgumentException('service_response_keys must be an array');
        }

        // Convert to JSON with error handling
        $apiResponseJson = $this->toJson($apiResponse);
        $serviceKeysJson = $this->toJson($serviceResponseKeys);

        return <<<PROMPT
You are an intelligent API response mapping assistant. Your task is to analyze an API response and map its keys to standardized service response keys.

INPUT DATA:
- API Response: $apiResponseJson
- Service Response Keys: $serviceKeysJson

CRITICAL RULES:
1. **UNIQUENESS CONSTRAINT**: Each service response key can only be mapped to ONE API key. Once a service key is used, it cannot be reused.
2. **ONE-TO-ONE MAPPING**: Each API key should map to exactly one service key OR get a new key created for it.
3. **BEST FIT SELECTION**: When multiple API keys could potentially match the same service key, choose the SINGLE BEST MATCH based on:
   - Semantic accuracy (exact meaning match)
   - Data type compatibility
   - Contextual relevance
   - Field specificity (more specific > less specific)
4. **NEW KEYS FOR DUPES**: If an API key would create a duplicate mapping (same service key already used), create a new key for it instead.

Your task:
1. Analyze all API response keys and their values
2. For each service response key, evaluate ALL potential API key matches and select the SINGLE BEST one
3. Map only the best API key to each service key
4. For remaining API keys (including those that would cause duplicates), create new snake_case key names
5. Return a JSON object with the mappings in this format:
   {
     "mappings": {
       "original_api_key1": "matched_service_key1",
       "original_api_key2": "matched_service_key2",
       ...
     },
     "new_keys_created": {
       "original_api_key1": "new_key1",
       "original_api_key2": "new_key2",
       ...
     }
   }

DECISION PROCESS:
1. First, identify API keys that have EXACT or NEAR-EXACT matches to service keys (semantic matches)
2. For each service key, if multiple API keys match:
   - Choose the most semantically precise match
   - Consider data type compatibility (dates to date fields, numbers to numeric fields, etc.)
   - Consider field specificity (city > location > region > country, apply_url > link > url)
3. Once a service key is assigned, mark it as "used" and DO NOT reuse it
4. For API keys that are similar but not the best match, create descriptive new keys

MAPPING PRIORITIES (in order):
1. Exact semantic match (e.g., "description" → "job_description")
2. Direct synonym match (e.g., "title" → "job_title")
3. Contextual match with proper data type (e.g., date string → "date")
4. General category match (last resort)

GOOD PRACTICES:
- If an API key contains multiple values (like "Colchester,, London"), map to the most specific available service key
- For URLs, prefer "apply_url" over generic "link" or "url" for job application contexts
- For company data, prefer "company" over "company-name" or other variants
- For IDs, match ID-like fields to ID service keys
- Create new keys for fields that don't have unique service key matches

BAD PRACTICES TO AVOID:
- Mapping multiple API keys to the same service key
- Mapping fields with incompatible data types (e.g., boolean to numeric field)
- Forcing a match when no good match exists
- Reusing service keys

Now process the input data and return the mapping result. Ensure each service key appears AT MOST once in the mappings.
PROMPT;
    }
}

<?php
/**
 * AI-Pulse Analysis Modes
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analysis modes definitions
 */
class AI_Pulse_Modes {

    /**
     * Get all available modes
     *
     * @return array
     */
    public static function get_all_modes() {
        return array(
            'SUMMARY' => array(
                'name' => 'Summary',
                'description' => 'General trend analysis (5 rising themes)',
                'icon' => '📊',
            ),
            'FAQS' => array(
                'name' => 'FAQs',
                'description' => 'Common buyer questions with answers',
                'icon' => '❓',
            ),
            'STATS' => array(
                'name' => 'Stats',
                'description' => 'Verified market statistics with citations',
                'icon' => '📈',
            ),
            'FORECAST' => array(
                'name' => 'Forecast',
                'description' => 'Seasonality and demand windows',
                'icon' => '🔮',
            ),
            'GAPS' => array(
                'name' => 'Gaps',
                'description' => 'Opportunity gaps in the market',
                'icon' => '🎯',
            ),
            'LOCAL' => array(
                'name' => 'Local',
                'description' => 'Regional trends (Birmingham/West Midlands focus)',
                'icon' => '📍',
            ),
            'WINS' => array(
                'name' => 'Wins',
                'description' => 'Anonymised micro-case studies',
                'icon' => '🏆',
            ),
            'GLOSSARY' => array(
                'name' => 'Glossary',
                'description' => 'Trending terminology definitions',
                'icon' => '📖',
            ),
            'PLATFORMS' => array(
                'name' => 'Platforms',
                'description' => 'Emerging search platforms (AI search, social)',
                'icon' => '🌐',
            ),
            'PULSE' => array(
                'name' => 'Pulse',
                'description' => 'B2B buyer intent signals',
                'icon' => '💼',
            ),
            'EXPLORER' => array(
                'name' => 'Explorer',
                'description' => 'Interactive trend themes',
                'icon' => '🔍',
            ),
            'ALL' => array(
                'name' => 'All (Mega Dashboard)',
                'description' => 'Generates all 11 modes in one API call',
                'icon' => '🎛️',
            ),
        );
    }

    /**
     * Get system instruction template
     *
     * @return string
     */
    public static function get_system_instruction() {
        return "You are a Senior Strategic Consultant for a UK Digital Agency.
CURRENT DATE: {current_date}.
ANALYSIS WINDOW: {date_range} ({period_description}).
TARGET AUDIENCE: UK Business Owners & Marketing Directors.
LANGUAGE: British English (en-GB). Use 's' instead of 'z' (e.g., analyse, optimise, prioritising).
CURRENCY: GBP (£) if applicable.

CRITICAL RULES:
1. TIME ACCURACY: You must ONLY use data/search results published between {date_range}. Do not use older evergreen content.
2. AUTHORITY SOURCES: Prioritise official documentation, UK industry reports, major news (BBC, Reuters, The Guardian), government sites (.gov.uk), and academic research. Avoid generic SEO blogs or agencies.
3. NO FAKE DATA: Do not invent stats. If no exact stat exists, provide a qualitative trend from a reputable source.
4. SERVICE-LED TONE: Write as if for a service landing page (e.g., \"What we do\", \"Why it matters\").
5. FORMAT: Output MUST be valid JSON. No conversational preamble.
6. LOCATION FOCUS: When analysing local trends, default to {location} unless specified otherwise.
7. KEYWORD FORMATTING: Always capitalise acronyms (SEO, PPC, ROI, CRM, API, UX, UI, etc.) and use title case for multi-word keywords (e.g., \"Web Design\", \"Digital Marketing\", \"Content Strategy\"). This applies to ALL output text including headings, summaries, insights, and content.";
    }

    /**
     * Get user prompt for a specific mode
     *
     * @param string $mode Mode identifier
     * @return string|false Prompt template or false if mode not found
     */
    public static function get_prompt($mode) {
        $prompts = array(
            'SUMMARY' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (SUMMARY): Identify top 5 rising {keyword}-related search themes from Trends/News in the last {period_description}. For each theme: (1) plain-English insight, (2) why it matters for UK SMEs, (3) what we do about it on projects. Output short, service-page-ready bullets.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Tight 'This month in [keyword]' block (max 50 words)\",
  \"trends\": [
    {
      \"term\": \"Theme\",
      \"insight\": \"Plain-English Insight\",
      \"implication\": \"Why it matters for UK SMEs\",
      \"action\": \"What we do (Service Action)\"
    }
  ]
}",

            'FAQS' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (FAQS): Identify the top 5 most-asked questions about {keyword} from UK business owners in the last {period_description}. Provide clear, actionable answers (2-3 sentences each).

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Brief intro to these FAQs (max 30 words)\",
  \"faqs\": [
    {
      \"question\": \"Question text?\",
      \"answer\": \"Clear, actionable answer (2-3 sentences)\"
    }
  ]
}",

            'STATS' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (STATS): Find 5 verified statistics about {keyword} from the last {period_description}. Each stat must have a credible source (UK industry report, government data, major news outlet).

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Brief context for these stats (max 30 words)\",
  \"stats\": [
    {
      \"stat\": \"Statistic text with number\",
      \"source\": \"Source name\",
      \"context\": \"Why this matters (1 sentence)\"
    }
  ]
}",

            'FORECAST' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (FORECAST): Analyse seasonality and demand patterns for {keyword} services. Identify upcoming high-demand windows and strategic timing recommendations.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Seasonality overview (max 40 words)\",
  \"periods\": [
    {
      \"timeframe\": \"Month/Quarter\",
      \"demand\": \"High/Medium/Low\",
      \"insight\": \"Why demand changes\",
      \"recommendation\": \"Strategic action\"
    }
  ]
}",

            'GAPS' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (GAPS): Identify 5 opportunity gaps in the {keyword} market from the last {period_description}. Focus on underserved needs, emerging requirements, or competitive weaknesses.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Market gap overview (max 40 words)\",
  \"gaps\": [
    {
      \"gap\": \"Gap description\",
      \"opportunity\": \"Business opportunity\",
      \"action\": \"How we address it\"
    }
  ]
}",

            'LOCAL' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (LOCAL): Analyse {keyword} trends specific to {location} from the last {period_description}. Focus on regional developments, local business needs, and area-specific opportunities.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Local market overview (max 40 words)\",
  \"trends\": [
    {
      \"trend\": \"Local trend\",
      \"impact\": \"Impact on local businesses\",
      \"action\": \"Local service opportunity\"
    }
  ]
}",

            'WINS' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (WINS): Create 3 anonymised micro-case studies showing successful {keyword} implementations from the last {period_description}. Focus on measurable outcomes.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Success stories overview (max 30 words)\",
  \"cases\": [
    {
      \"scenario\": \"Business challenge\",
      \"solution\": \"What was implemented\",
      \"result\": \"Measurable outcome\"
    }
  ]
}",

            'GLOSSARY' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (GLOSSARY): Define 5 trending terms related to {keyword} from the last {period_description}. Focus on new terminology, evolving concepts, or buzzwords gaining traction.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Terminology trends (max 30 words)\",
  \"terms\": [
    {
      \"term\": \"Term name\",
      \"definition\": \"Clear definition (2 sentences)\",
      \"relevance\": \"Why it matters now\"
    }
  ]
}",

            'PLATFORMS' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (PLATFORMS): Identify emerging search and discovery platforms affecting {keyword} visibility from the last {period_description}. Include AI search, social platforms, and new channels.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Platform landscape (max 40 words)\",
  \"platforms\": [
    {
      \"platform\": \"Platform name\",
      \"trend\": \"How it's changing search\",
      \"opportunity\": \"Marketing opportunity\"
    }
  ]
}",

            'PULSE' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (PULSE): Identify 5 B2B buyer intent signals for {keyword} services from the last {period_description}. Focus on what triggers purchase decisions.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Buyer intent overview (max 40 words)\",
  \"signals\": [
    {
      \"signal\": \"Intent indicator\",
      \"trigger\": \"What prompts this\",
      \"response\": \"How to engage\"
    }
  ]
}",

            'EXPLORER' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (EXPLORER): Map 5 interconnected trend themes for {keyword} from the last {period_description}. Show how different trends relate and influence each other.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Trend ecosystem (max 40 words)\",
  \"themes\": [
    {
      \"theme\": \"Theme name\",
      \"connections\": \"How it connects to other trends\",
      \"impact\": \"Overall market impact\"
    }
  ]
}",

            'ALL' => "TARGET KEYWORD: \"{keyword}\"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (ALL - MEGA DASHBOARD): Generate a comprehensive analysis covering all aspects: trends, FAQs, stats, forecast, gaps, local insights, wins, glossary, platforms, pulse, and explorer. This is a complete market intelligence dashboard.

OUTPUT FORMAT (JSON ONLY):
{
  \"summary\": \"Overall market snapshot (max 60 words)\",
  \"trends\": [...],
  \"faqs\": [...],
  \"stats\": [...],
  \"forecast\": [...],
  \"gaps\": [...],
  \"local\": [...],
  \"wins\": [...],
  \"glossary\": [...],
  \"platforms\": [...],
  \"pulse\": [...],
  \"explorer\": [...]
}"
        );

        return isset($prompts[$mode]) ? $prompts[$mode] : false;
    }

    /**
     * Get expected JSON structure for a mode
     *
     * @param string $mode Mode identifier
     * @return array|false Structure definition or false
     */
    public static function get_structure($mode) {
        $structures = array(
            'SUMMARY' => array('summary', 'trends'),
            'FAQS' => array('summary', 'faqs'),
            'STATS' => array('summary', 'stats'),
            'FORECAST' => array('summary', 'periods'),
            'GAPS' => array('summary', 'gaps'),
            'LOCAL' => array('summary', 'trends'),
            'WINS' => array('summary', 'cases'),
            'GLOSSARY' => array('summary', 'terms'),
            'PLATFORMS' => array('summary', 'platforms'),
            'PULSE' => array('summary', 'signals'),
            'EXPLORER' => array('summary', 'themes'),
            'ALL' => array('summary', 'trends', 'faqs', 'stats', 'forecast', 'gaps', 'local', 'wins', 'glossary', 'platforms', 'pulse', 'explorer'),
        );

        return isset($structures[$mode]) ? $structures[$mode] : false;
    }
}

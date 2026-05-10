<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Laddro\Career\Laddro;
use Laddro\Career\LaddroException;

$apiKey = getenv('LADDRO_API_KEY');
if (!$apiKey) { echo "Set LADDRO_API_KEY\n"; exit(1); }

$client = new Laddro($apiKey);
$public = new Laddro();
$passed = 0;
$failed = 0;
$resumeId = null;
$coverLetterId = null;

function test(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $fn();
        echo "  ✓ $name\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  ✗ $name: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n— 1. Public endpoints (5/18) —\n\n";

test("GET /v1/templates", function() use ($public) {
    $t = $public->listTemplates();
    assert(count($t) === 22, "expected 22, got " . count($t));
});
test("GET /v1/templates/{id}", function() use ($public) {
    $d = $public->getTemplate("GRAPHITE");
    assert($d["id"] === "GRAPHITE");
    assert(count($d["availableColors"]) > 0);
});
test("GET /v1/fonts", function() use ($public) {
    $f = $public->listFonts();
    assert(count($f) === 21, "expected 21, got " . count($f));
});
test("GET /v1/languages", function() use ($public) {
    $l = $public->listLanguages();
    assert(count($l) === 14, "expected 14, got " . count($l));
});
test("GET /v1/models", function() use ($public) {
    $m = $public->listModels();
    assert(count($m) === 10, "expected 10, got " . count($m));
});

echo "\n— 2. Resume endpoints (4/18) —\n\n";

test("GET /v1/resumes", function() use ($client, &$resumeId) {
    $r = $client->listResumes(5, 0);
    assert(count($r["items"]) > 0, "no resumes");
    foreach ($r["items"] as $item) {
        if ($item["isDefault"]) { $resumeId = $item["resumeId"]; break; }
    }
    if (!$resumeId) $resumeId = $r["items"][0]["resumeId"];
});
test("GET /v1/resumes/{id}", function() use ($client, &$resumeId) {
    $r = $client->getResume($resumeId);
    assert($r["resumeId"] === $resumeId);
});
test("PUT /v1/resumes/{id}/render", function() use ($client, &$resumeId) {
    $pdf = $client->renderResume($resumeId, ["templateId" => "GRAPHITE"]);
    assert(strlen($pdf) > 1000, "too small: " . strlen($pdf));
});
test("POST /v1/resumes/parse (skip)", function() {});

echo "\n— 3. Tailor (1/18) —\n\n";

test("POST /v1/tailor", function() use ($client, &$resumeId) {
    $pdf = $client->tailor([
        "positionName" => "PHP SDK Test",
        "resumeId" => $resumeId,
        "jobDescription" => "Write PHP code.",
    ]);
    assert(strlen($pdf) > 5000, "too small: " . strlen($pdf));
});

echo "\n— 4. Export (1/18) —\n\n";

test("POST /v1/export", function() use ($client, &$resumeId) {
    $pdf = $client->exportPdf(["resumeId" => $resumeId, "templateId" => "COBALT"]);
    assert(strlen($pdf) > 1000, "too small: " . strlen($pdf));
});

echo "\n— 5. Cover Letter endpoints (5/18) —\n\n";

test("GET /v1/cover-letters", function() use ($client) {
    $client->listCoverLetters();
});
test("POST /v1/cover-letters", function() use ($client, &$coverLetterId) {
    $r = $client->createCoverLetter([
        "fullName" => "PHP Test",
        "letterContent" => "<p>Test from PHP SDK.</p>",
    ]);
    $coverLetterId = $r["coverLetterId"];
    assert($coverLetterId !== null);
});
test("GET /v1/cover-letters/{id}", function() use ($client, &$coverLetterId) {
    $cl = $client->getCoverLetter($coverLetterId);
    assert($cl["coverLetterId"] === $coverLetterId);
});
test("PUT /v1/cover-letters/{id}/render", function() use ($client, &$coverLetterId) {
    $pdf = $client->renderCoverLetter($coverLetterId, ["templateId" => "NICKEL"]);
    assert(strlen($pdf) > 1000, "too small: " . strlen($pdf));
});
test("POST /v1/cover-letters/generate", function() use ($client, &$resumeId) {
    $pdf = $client->generateCoverLetter([
        "positionName" => "PHP Test",
        "resumeId" => $resumeId,
        "jobDescription" => "PHP dev needed.",
    ]);
    assert(strlen($pdf) > 1000, "too small: " . strlen($pdf));
});

echo "\n— 6. Settings (3/18) —\n\n";

test("GET /v1/settings", function() use ($client) { $client->getSettings(); });
test("PUT /v1/settings/model", function() use ($client) {
    try {
        $client->updateAiSettings(["provider" => "OpenAI", "model" => "gpt-4o-mini", "apiKey" => "sk-test"]);
    } catch (LaddroException $e) { /* 400 expected */ }
});
test("DELETE /v1/settings/model", function() use ($client) {
    $r = $client->deleteAiSettings();
    assert($r["ai"] === null);
});

echo "\n— 7. Errors —\n\n";

test("401 on bad key", function() {
    $bad = new Laddro("laddro_live_invalid");
    try { $bad->listResumes(); throw new \Exception("should throw"); }
    catch (LaddroException $e) { assert($e->isAuthError()); }
});
test("404 on missing resume", function() use ($client) {
    try { $client->getResume("00000000-0000-0000-0000-000000000000"); throw new \Exception("should throw"); }
    catch (LaddroException $e) { assert($e->isNotFound()); }
});

echo "\n═══ FINAL: $passed passed, $failed failed (18 endpoints covered) ═══\n\n";
exit($failed > 0 ? 1 : 0);

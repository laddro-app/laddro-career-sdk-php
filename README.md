# laddro/career-sdk

PHP SDK for the [Laddro Career API](https://api.laddro.com/reference).

## Install

```bash
composer require laddro/career-sdk
```

## Usage

```php
use Laddro\Career\Laddro;

$laddro = new Laddro('laddro_live_...');

// List resumes
$resumes = $laddro->listResumes();
foreach ($resumes['items'] as $resume) {
    echo $resume['title'] . "\n";
}

// Tailor a resume
$pdf = $laddro->tailor([
    'positionName' => 'Senior Frontend Engineer',
    'jobUrl' => 'https://jobs.example.com/sfe',
]);
file_put_contents('tailored.pdf', $pdf);

// Generate cover letter
$cl = $laddro->generateCoverLetter([
    'positionName' => 'Product Manager',
    'jobUrl' => 'https://jobs.example.com/pm',
]);

// Browse templates (no auth)
$laddro = new Laddro();
$templates = $laddro->listTemplates();

// Configure BYOK
$laddro->updateAiSettings([
    'provider' => 'Anthropic',
    'model' => 'claude-sonnet-4-20250514',
    'apiKey' => 'sk-ant-...',
]);
```

## Links

- [laddro.com](https://laddro.com)
- [API Reference](https://api.laddro.com/reference)
- [Docs](https://docs.laddro.com)
- [GitHub](https://github.com/laddro-app)

## License

MIT

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSwaggerDocs extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Generate OpenAPI/Swagger documentation';

    public function handle(): int
    {
        try {
            $this->info('Generating OpenAPI specification...');

            // Use Swagger\scan from swagger-php library
            $swagger = \Swagger\scan([
                base_path('storage/api-docs'),
                base_path('app/Http/Controllers'),
            ]);

            $outputPath = storage_path('api-docs');
            if (!is_dir($outputPath)) {
                mkdir($outputPath, 0755, true);
            }

            // Save as JSON
            file_put_contents(
                $outputPath . '/api-docs.json',
                json_encode($swagger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Save as YAML
            file_put_contents(
                $outputPath . '/api-docs.yaml',
                $swagger->toYaml()
            );

            $this->info('✓ OpenAPI specification generated successfully!');
            $this->info('JSON: ' . $outputPath . '/api-docs.json');
            $this->info('YAML: ' . $outputPath . '/api-docs.yaml');
            $this->newLine();
            $this->info('Access documentation at: http://localhost:8000/api/docs');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error generating documentation: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

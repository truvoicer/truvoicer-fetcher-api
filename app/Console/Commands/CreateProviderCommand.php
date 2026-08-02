<?php

namespace App\Console\Commands;

use App\Services\Tools\VariablesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
use Truvoicer\TfDbReadCore\Models\Category;
use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\S;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Services\ApiServices\ApiService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrService;
use Truvoicer\TfDbReadCore\Services\Category\CategoryService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;

class CreateProviderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provider:create
                            {--internal : Create the internal provider without prompts}
                            {--overwrite : Automatically overwrite existing provider without prompting}
                            {--skip-service : Skip creating/updating service}
                            {--skip-srs : Skip creating/updating service requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new provider with interactive prompts or create the internal provider';

    protected ProviderService $providerService;

    protected ApiService $apiService;

    protected SrService $srService;

    protected CategoryService $categoryService;

    protected VariablesService $variablesService;

    protected ?User $adminUser = null;

    protected ?Provider $currentProvider = null;

    protected ?S $currentService = null;

    public function __construct(
        ProviderService $providerService,
        ApiService $apiService,
        SrService $srService,
        CategoryService $categoryService,
        VariablesService $variablesService
    ) {
        parent::__construct();
        $this->providerService = $providerService;
        $this->apiService = $apiService;
        $this->srService = $srService;
        $this->categoryService = $categoryService;
        $this->variablesService = $variablesService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Get user by email
            $this->initializeUser();

            if ($this->option('internal')) {
                $result = $this->createInternalProvider();

                if ($result !== 0) {
                    return $result;
                }

                // Handle service for internal provider
                if (! $this->option('skip-service')) {
                    $this->handleInternalService();
                }

                // Handle service requests for internal provider
                if (! $this->option('skip-srs')) {
                    $this->handleInternalServiceRequests();
                }

                return 0;
            }

            $result = $this->createInteractiveProvider();

            if ($result !== 0) {
                return $result;
            }

            // Ask about creating service for non-internal provider
            if (! $this->option('skip-service')) {
                $this->handleServicePrompt();
            }

            // Ask about creating service requests for non-internal provider
            if (! $this->option('skip-srs')) {
                $this->handleServiceRequestsPrompt();
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Initialize user by asking for email
     */
    protected function initializeUser(): void
    {
        $this->info('🔐 User Authentication');
        $this->newLine();

        $maxAttempts = 3;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $email = $this->ask('Enter user email address');

            if (empty($email)) {
                $this->error('❌ Email address is required.');
                $attempts++;

                continue;
            }

            $this->adminUser = User::where('email', $email)->first();

            if ($this->adminUser) {
                $this->info("✓ Authenticated as: ({$this->adminUser->email})");
                $this->newLine();

                return;
            }

            $this->error("❌ No user found with email: {$email}");
            $attempts++;

            if ($attempts < $maxAttempts) {
                $remaining = $maxAttempts - $attempts;
                $this->warn("Please try again. ({$remaining} attempts remaining)");
            }
        }

        throw new \Exception('Failed to authenticate after '.$maxAttempts.' attempts. Please check the user email and try again.');
    }

    /**
     * Handle service prompt for non-internal provider
     */
    protected function handleServicePrompt(): void
    {
        $this->newLine();
        if ($this->confirm('Do you want to create a service for this provider?', false)) {
            $this->createOrUpdateService('default');
        }
    }

    /**
     * Handle service requests prompt for non-internal provider
     */
    protected function handleServiceRequestsPrompt(): void
    {
        if (! $this->currentService) {
            $this->warn("\n⚠️  No service found. Please create a service first before creating service requests.");
            if ($this->confirm('Do you want to create a service now?', false)) {
                $this->createOrUpdateService('default');
                if ($this->currentService && $this->confirm('Do you want to create service requests for this provider?', false)) {
                    $this->createServiceRequestsForProvider();
                }
            }

            return;
        }

        $this->newLine();
        if ($this->confirm('Do you want to create service requests for this provider?', false)) {
            $this->createServiceRequestsForProvider();
        }
    }

    /**
     * Create service requests for the current provider
     */
    protected function createServiceRequestsForProvider(): void
    {
        if (! $this->currentService) {
            $this->error("❌ Cannot create service requests: No service found for provider {$this->currentProvider->name}");

            return;
        }

        $this->info("\n📝 Creating service requests for provider: {$this->currentProvider->name}");
        $this->info("   Using service: {$this->currentService->name} (ID: {$this->currentService->id})");
        $this->newLine();

        foreach (SrType::cases() as $srType) {
            $this->createOrUpdateServiceRequest($srType);
        }
    }

    /**
     * Handle internal service creation/update
     */
    protected function handleInternalService(): void
    {
        $this->newLine();

        if ($this->option('overwrite')) {
            $this->info('Auto-creating/updating internal service...');
            $this->createOrUpdateService('internal');
        } elseif ($this->confirm('Do you want to create/update the internal service?', true)) {
            $this->createOrUpdateService('internal');
        } else {
            $this->info('Skipped creating internal service.');
        }
    }

    /**
     * Handle internal service requests creation/update
     */
    protected function handleInternalServiceRequests(): void
    {
        if (! $this->currentService) {
            $this->warn("\n⚠️  No service found. Please create a service first before creating service requests.");
            if ($this->confirm('Do you want to create the internal service now?', true)) {
                $this->createOrUpdateService('internal');
                if ($this->currentService && $this->confirm('Do you want to create internal service requests?', true)) {
                    $this->createInternalServiceRequests();
                }
            }

            return;
        }

        $this->newLine();

        if ($this->option('overwrite')) {
            $this->info('Auto-creating/updating internal service requests...');
            $this->createInternalServiceRequests();
        } elseif ($this->confirm('Do you want to create/update internal service requests?', true)) {
            $this->createInternalServiceRequests();
        } else {
            $this->info('Skipped creating internal service requests.');
        }
    }

    /**
     * Create internal service requests for all SrTypes
     */
    protected function createInternalServiceRequests(): void
    {
        if (! $this->currentService) {
            $this->error('❌ Cannot create service requests: No service found.');

            return;
        }

        $this->info("\n📝 Creating/updating internal service requests...");
        $this->info("   Using service: {$this->currentService->name} (ID: {$this->currentService->id})");
        $this->newLine();

        foreach (SrType::cases() as $srType) {
            $this->createOrUpdateServiceRequest($srType);
        }
    }

    /**
     * Create or update a service
     */
    protected function createOrUpdateService(string $type): void
    {
        $serviceName = $type === 'internal' ? 'internal' : $this->promptForServiceName();
        $serviceLabel = $type === 'internal' ? 'Internal Service' : $this->promptForServiceLabel($serviceName);

        if (! $serviceName) {
            $this->warn('⚠️  Service creation skipped.');

            return;
        }

        // Check if service already exists
        $existingService = S::where('name', $serviceName)->first();

        if ($existingService) {
            $this->warn("⚠️  Service '{$serviceName}' already exists (ID: {$existingService->id}).");

            $overwrite = $this->option('overwrite') || $this->confirm(
                "Do you want to overwrite the existing '{$serviceName}' service?",
                false
            );

            if (! $overwrite) {
                $this->info("   Keeping existing service '{$serviceName}'.");
                $this->currentService = $existingService;
                $this->newLine();

                return;
            }

            // Update existing service
            $this->info("   Updating service '{$serviceName}'...");

            $updateData = [
                'label' => $serviceLabel,
            ];

            if ($serviceName !== $existingService->name) {
                $updateData['name'] = $serviceName;
            }

            try {
                $this->apiService->setThrowException(true);
                $result = $this->apiService->updateService($existingService, $updateData);

                if ($result) {
                    $this->info("   ✓ Service '{$serviceName}' updated successfully.");
                    $this->currentService = $existingService->refresh();
                } else {
                    $this->error("   ✗ Failed to update service '{$serviceName}'.");
                }
            } catch (\Exception $e) {
                $this->error("   ✗ Error updating service '{$serviceName}': ".$e->getMessage());
            }
        } else {
            // Create new service
            $this->info("   Creating service '{$serviceName}'...");

            $createData = [
                'name' => $serviceName,
                'label' => $serviceLabel,
            ];

            try {
                $this->apiService->setThrowException(true);
                $result = $this->apiService->createService($this->adminUser, $createData);

                if ($result) {
                    $this->info("   ✓ Service '{$serviceName}' created successfully.");
                    $this->currentService = S::where('name', $serviceName)->first();
                } else {
                    $this->error("   ✗ Failed to create service '{$serviceName}'.");
                }
            } catch (\Exception $e) {
                $this->error("   ✗ Error creating service '{$serviceName}': ".$e->getMessage());
            }
        }

        $this->newLine();
    }

    /**
     * Prompt for service name
     */
    protected function promptForServiceName(): ?string
    {
        $this->info('Service Name:');
        $this->line('  • Must be unique');
        $this->line('  • Use lowercase letters, numbers, underscores, or hyphens');
        $this->line('  • Must start with a letter');
        $this->line('  • Example: "my_service" or "my-service"');
        $this->newLine();

        return $this->askValid(
            'Enter service name (or press Ctrl+C to cancel)',
            'service_name',
            function ($value) {
                if (empty($value)) {
                    return false; // Allow cancellation
                }

                if (! preg_match('/^[a-z][a-z0-9_\-]*$/', $value)) {
                    return 'Service name must start with a letter and only contain lowercase letters, numbers, underscores, or hyphens.';
                }

                return true;
            }
        );
    }

    /**
     * Prompt for service label
     */
    protected function promptForServiceLabel(string $defaultName): string
    {
        $this->info('Service Label:');
        $this->line('  • Human-readable name');
        $this->line('  • Example: "My Service" or "Internal API Service"');
        $this->newLine();

        $defaultLabel = ucfirst(str_replace(['_', '-'], ' ', $defaultName));
        $label = $this->ask('Enter service label (press Enter for default)', $defaultLabel);

        return empty($label) ? $defaultLabel : $label;
    }

    /**
     * Create or update a service request for a given type
     */
    protected function createOrUpdateServiceRequest(SrType $srType): void
    {
        if (! $this->currentService) {
            $this->error("   ✗ Cannot create service request '{$srType->value}': No service available.");

            return;
        }

        $srName = "{$this->currentProvider->name}_{$srType->value}";
        $srLabel = ucfirst(str_replace('_', ' ', $this->currentProvider->name))." {$srType->label()}";

        // Check if service request already exists
        $existingSr = Sr::where('name', $srName)
            ->where('provider_id', $this->currentProvider->id)
            ->first();

        if ($existingSr) {
            $this->warn("⚠️  Service Request '{$srName}' already exists (ID: {$existingSr->id}).");

            $overwrite = $this->option('overwrite') || $this->confirm(
                "Do you want to overwrite the existing '{$srName}' service request?",
                false
            );

            if (! $overwrite) {
                $this->info("   Skipped updating '{$srName}'.");
                $this->newLine();

                return;
            }

            // Update existing service request
            $this->info("   Updating service request '{$srName}'...");

            $updateData = $this->buildServiceRequestData($srType, $srName, $srLabel);

            try {
                $this->srService->setThrowException(true);
                $result = $this->srService->updateServiceRequest($existingSr, $updateData);

                if ($result) {
                    $this->info("   ✓ Service Request '{$srName}' updated successfully.");
                } else {
                    $this->error("   ✗ Failed to update service request '{$srName}'.");
                }
            } catch (\Exception $e) {
                $this->error("   ✗ Error updating service request '{$srName}': ".$e->getMessage());
            }
        } else {
            // Create new service request
            $this->info("   Creating service request '{$srName}'...");

            $createData = $this->buildServiceRequestData($srType, $srName, $srLabel);
            $createData['label'] = $srLabel; // Label is required for creation
            $createData['service'] = $this->currentService->id; // Service ID is required

            try {
                $this->srService->setThrowException(true);
                $result = $this->srService->createServiceRequest($this->currentProvider, $createData, false);

                if ($result) {
                    $this->info("   ✓ Service Request '{$srName}' created successfully.");
                } else {
                    $this->error("   ✗ Failed to create service request '{$srName}'.");
                }
            } catch (\Exception $e) {
                $this->error("   ✗ Error creating service request '{$srName}': ".$e->getMessage());
            }
        }

        $this->newLine();
    }

    /**
     * Build service request data array
     */
    protected function buildServiceRequestData(SrType $srType, string $name, string $label): array
    {
        $data = [
            'name' => $name,
            'type' => $srType->value,
            'default_sr' => false,
            'service' => $this->currentService->id,
        ];

        // Add type-specific data
        switch ($srType) {
            case SrType::LIST:
                $data['pagination_type'] = 'page';
                break;
            case SrType::SINGLE:
                // Single type specific config
                break;
            case SrType::DETAIL:
                // Detail type specific config
                break;
            case SrType::MIXED:
                // Mixed type specific config
                break;
        }

        return $data;
    }

    /**
     * Create the internal provider
     */
    protected function createInternalProvider(): int
    {
        $providerName = 'internal';
        $this->info('🏗️  Creating internal provider...');
        $this->newLine();

        // Check if provider already exists
        $existingProvider = Provider::where('name', $providerName)->first();

        if ($existingProvider) {
            $this->warn("⚠️  Provider '{$providerName}' already exists (ID: {$existingProvider->id}).");

            $overwrite = $this->option('overwrite') || $this->confirm(
                "Do you want to overwrite the existing '{$providerName}' provider? This will delete the current provider and all its data.",
                false
            );

            if (! $overwrite) {
                $this->info('❌ Operation cancelled.');

                return 1;
            }

            $this->warn("🗑️  Deleting existing provider '{$providerName}'...");
            if (! $this->providerService->deleteProvider($existingProvider)) {
                throw new \Exception("Failed to delete existing provider '{$providerName}'.");
            }
            $this->info("✓ Existing provider '{$providerName}' deleted.");
            $this->newLine();

            // Create new provider
            $this->currentProvider = $this->createNewProvider($providerName);
        } else {
            // Create new provider
            $this->currentProvider = $this->createNewProvider($providerName);
        }

        if (! $this->currentProvider) {
            throw new \Exception("Failed to create provider '{$providerName}'.");
        }

        return 0;
    }

    /**
     * Create a new provider
     */
    protected function createNewProvider(string $providerName): ?Provider
    {
        // Prepare provider data
        $providerData = [
            'name' => $providerName,
            'label' => 'Internal Provider',
            'global' => true,
            'categories' => $this->getDefaultCategories(),
        ];

        // Show summary
        $this->info('📋 Provider Summary:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $providerData['name']],
                ['Label', $providerData['label']],
                ['Global', 'Yes'],
                ['Categories', implode(', ', $this->getCategoryNames($providerData['categories']))],
                ['Created By', '('.$this->adminUser->email.')'],
            ]
        );
        $this->newLine();

        // Confirm creation
        if (! $this->confirm('Do you want to create this provider?', true)) {
            $this->info('❌ Operation cancelled.');

            return null;
        }

        // Create the provider
        $this->info("📝 Creating provider '{$providerName}'...");

        DB::beginTransaction();
        try {
            $this->providerService->setThrowException(true);
            $this->providerService->createProvider($this->adminUser, $providerData);
            DB::commit();
            $this->newLine();
            $this->info("✅ Provider '{$providerName}' created successfully!");
            $this->info('   You can now use this provider for service requests.');

            return Provider::where('name', $providerName)->first();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a provider with interactive prompts
     */
    protected function createInteractiveProvider(): int
    {
        $this->info('🏗️  Creating a new provider...');
        $this->newLine();

        // Get provider details with validation
        $providerData = $this->promptProviderDetails();

        // Show summary
        $this->newLine();
        $this->info('📋 Provider Summary:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $providerData['name']],
                ['Label', $providerData['label']],
                ['Global', $providerData['global'] ? 'Yes' : 'No'],
                ['Categories', implode(', ', $this->getCategoryNames($providerData['categories'] ?? []))],
                ['Created By', '('.$this->adminUser->email.')'],
            ]
        );
        $this->newLine();

        // Confirm creation
        if (! $this->confirm('Do you want to create this provider?', true)) {
            $this->info('❌ Operation cancelled.');

            return 1;
        }

        // Create the provider
        $this->info("📝 Creating provider '{$providerData['name']}'...");

        DB::beginTransaction();
        try {
            $this->providerService->setThrowException(true);
            $this->providerService->createProvider($this->adminUser, $providerData);
            DB::commit();

            $this->currentProvider = Provider::where('name', $providerData['name'])->first();

            $this->newLine();
            $this->info("✅ Provider '{$providerData['name']}' created successfully!");
            $this->info('   You can now:');
            $this->info('   • Create a service for this provider');
            $this->info('   • Add service requests to this provider');
            $this->info('   • Configure provider properties');
            $this->info('   • Assign permissions to users');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Prompt for provider details interactively
     */
    protected function promptProviderDetails(): array
    {
        $this->info('📝 Please provide the following information:');
        $this->newLine();

        // Get provider name
        $this->info('Provider Name:');
        $this->line('  • Must be unique');
        $this->line('  • Use lowercase letters, numbers, underscores, or hyphens');
        $this->line('  • Must start with a letter');
        $this->line('  • Example: "my_provider" or "my-provider"');
        $this->newLine();

        $name = $this->askValid(
            'Enter provider name',
            'name',
            function ($value) {
                if (empty($value)) {
                    return 'Provider name is required.';
                }

                $existing = Provider::where('name', $value)->first();
                if ($existing) {
                    return "Provider with name '{$value}' already exists. Please choose a different name.";
                }

                if (! preg_match('/^[a-z][a-z0-9_\-]*$/', $value)) {
                    return 'Provider name must start with a letter and only contain lowercase letters, numbers, underscores, or hyphens.';
                }

                return true;
            }
        );

        // Get provider label
        $this->newLine();
        $this->info('Provider Label:');
        $this->line('  • Human-readable name');
        $this->line('  • Example: "My Provider" or "Internal Service Provider"');
        $this->newLine();

        $defaultLabel = ucfirst(str_replace(['_', '-'], ' ', $name));
        $label = $this->ask('Enter provider label (press Enter for default)', $defaultLabel);

        if (empty($label)) {
            $label = $defaultLabel;
        }

        // Ask if provider is global
        $this->newLine();
        $this->info('Global Provider:');
        $this->line('  • Global providers are available to all users');
        $this->line('  • Non-global providers are user-specific');
        $this->newLine();

        $global = $this->confirm('Is this a global provider?', false);

        // Get categories
        $categories = $this->promptCategories();

        return [
            'name' => $name,
            'label' => $label,
            'global' => $global,
            'categories' => $categories,
        ];
    }

    /**
     * Prompt for category selection
     */
    protected function promptCategories(): array
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->newLine();
            $this->warn('⚠️  No categories found in the system.');
            $this->info('You can create categories later using: php artisan category:create');
            $this->newLine();

            if (! $this->confirm('Continue without categories?', true)) {
                $this->info('❌ Operation cancelled.');
                exit(0);
            }

            return [];
        }

        $this->newLine();
        $this->info('Categories:');
        $this->line('  • Assign one or more categories to this provider');
        $this->line('  • Categories help organize providers');
        $this->newLine();

        $this->info('Available categories:');

        $categoryOptions = [];
        foreach ($categories as $category) {
            $categoryOptions[$category->id] = "{$category->name} (ID: {$category->id})";
            $this->line("  • {$categoryOptions[$category->id]}");
        }

        $this->newLine();

        $selectedIds = $this->choice(
            'Select categories (comma-separated IDs or press Enter to skip)',
            array_values($categoryOptions),
            null,
            null,
            true
        );

        if (empty($selectedIds)) {
            $this->info('No categories selected.');

            return [];
        }

        // Extract IDs from selected options
        $selectedCategoryIds = [];
        foreach ($selectedIds as $selected) {
            preg_match('/ID: (\d+)/', $selected, $matches);
            if (isset($matches[1])) {
                $selectedCategoryIds[] = (int) $matches[1];
            }
        }

        $this->info('✓ Selected '.count($selectedCategoryIds).' category(ies)');

        return $selectedCategoryIds;
    }

    /**
     * Ask a question with validation
     */
    protected function askValid(string $question, string $field, callable $validator): ?string
    {
        $value = $this->ask($question);

        while (true) {
            $result = $validator($value);
            if ($result === true) {
                return $value;
            }

            if ($result === false) {
                return null;
            }

            $this->error('❌ '.$result);
            $value = $this->ask($question);
        }
    }

    /**
     * Get default categories for internal provider
     */
    protected function getDefaultCategories(): array
    {
        // Try to find or create default categories
        $defaultCategoryNames = ['General', 'Internal'];
        $categoryIds = [];

        foreach ($defaultCategoryNames as $categoryName) {
            $category = Category::firstOrCreate(
                ['name' => strtolower($categoryName), 'label' => $categoryName],
            );
            $categoryIds[] = $category->id;
        }

        return $categoryIds;
    }

    /**
     * Get category names from IDs
     */
    protected function getCategoryNames(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return ['None'];
        }

        $categories = Category::whereIn('id', $categoryIds)->get();

        return $categories->pluck('name')->toArray();
    }
}

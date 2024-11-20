<?php

namespace App\Console\Commands;

use App\Jobs\News\NewsAPIJob;
use Illuminate\Console\Command;

class FetchArticlesCommand extends Command
{
    protected $signature = 'articles:fetch {service?} {--all}';
    protected $description = 'Fetch articles from specified or all news APIs';

    public function handle(): void
    {
        $categories = config("services.news_apis.categories");
        $services = config("services.news_apis.services");

        $selectedService = $this->argument('service');
        $fetchAll = $this->option('all');

        if (!$selectedService && !$fetchAll) {
            $selectedService = $this->choice(
                'Which news service would you like to fetch from?',
                array_keys($services),
                0
            );
        }

        $servicesToProcess = $fetchAll ? array_keys($services) : [$selectedService];

        foreach ($servicesToProcess as $service) {
            foreach ($categories as $category) {
                $job = new NewsAPIJob($service, $category);
                $job->handle();
            }
            $this->info("Articles fetched from $service");
        }
    }
}

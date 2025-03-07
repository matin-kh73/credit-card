<?php

namespace App\Command;

use App\Service\ApiService;
use Exception;
use Symfony\Component\Console\{
    Attribute\AsCommand,
    Command\Command,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Style\SymfonyStyle
};
use Symfony\Contracts\HttpClient\Exception\{
    ClientExceptionInterface,
    RedirectionExceptionInterface,
    ServerExceptionInterface,
    TransportExceptionInterface
};

#[AsCommand(
    name: 'app:fetch-credit-cards',
    description: 'Fetch and update credit card data from the API'
)]
class FetchCreditCardsCommand extends Command
{
    public function __construct(private readonly ApiService $apiService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command fetches credit card data from the API and updates the database. Use --bank-id to fetch specific bank data or --force to force update all records.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force update all records');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $forceUpdate = $input->getOption('force');

        try {
            $result = $this->apiService->fetchAndUpdateCreditCards($forceUpdate);
            $io->success(sprintf(
                'Successfully processed credit cards%s. Total: %d, Added: %d, Updated: %d, Skipped: %d',
                $forceUpdate ? ' (force update)' : '',
                $result['total'],
                $result['added'],
                $result['updated'],
                $result['skipped']
            ));
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error fetching credit card data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

<?php

declare(strict_types=1);

namespace Tagdo\SolrGelinodasi\Command;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use Solarium\QueryType\Update\Query\Document\Document;
use StudioMitte\Youcard\Update\SlugPartnerUpdate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportCommand extends Command
{
    public function configure()
    {
        $this->setDescription('Import gelinodasi into TYPO3 solr');
    }

    const URL = 'http://vps2191614.fastwebserver.de:8983/solr/core_en/select?q=*:*';
    const DOCUMENTS_PER_PAGE = 1000;
    const PAGES = 2000;

    protected $mapping = [
        'digest' => 'digest_stringS',
        'boost' => 'boost_floatS',
        'id' => 'title',
        'url' => 'url',
        'content' => 'content',
        '_version_' => 'version_stringS'
    ];

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Welcome to solr indexer for gelinodasi');

        $type = 'tx_gelinodasi';
        $this->updateSlugs($io);

        $site = $this->getSolrSite();
        $connectionManager = GeneralUtility::makeInstance(ConnectionManager::class);
        $connection = $connectionManager->getConnectionByPageId(1, 0);
        $connection->getWriteService()->deleteByType($type);


        $progressBar = new ProgressBar($output, self::PAGES);
        $progressBar->start();
        $count = 0;

        for ($page = 0; $page < self::PAGES; $page++) {
            $url = self::URL . '&start=' . ($page * self::DOCUMENTS_PER_PAGE) . '&rows=' . self::DOCUMENTS_PER_PAGE;
            $response = json_decode((string)GeneralUtility::getUrl($url), true);
            $rawDocuments = $response['response']['docs'] ?? [];

            $documents = [];
            foreach ($rawDocuments as $raw) {
                $count++;
                $document = GeneralUtility::makeInstance(Document::class);

                // required fields
                $document->setField('id', sprintf('%s/%s/%s', $type, $site->getSiteHash(), $count));
                $document->setField('type', $type);
                $document->setField('appKey', 'EXT:solr');
                $document->setField('access', ['r:0']);
                $document->setField('site', $site->getDomain());
                $document->setField('siteHash', $site->getSiteHash());

                // uid, pid
                $document->setField('uid', $count);
                $document->setField('pid', 1);

                foreach ($this->mapping as $from => $to) {
                    $document->setField($to, $raw[$from]);
                }
                $document->setField('created', gmdate('Y-m-d\TH:i:s\Z', (int)$raw['tstamp']));

                $documents[] = $document;
            }

            $connection->getWriteService()->addDocuments($documents);
            $progressBar->advance();

        }

        $io->success(sprintf('Imported %s rows! Great job!', $count));
        return 0;
    }


    protected function updateSlugs(SymfonyStyle $io)
    {
    }

    protected function getSolrSite(): Site
    {
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);
        return $siteRepository->getFirstAvailableSite();
    }

}

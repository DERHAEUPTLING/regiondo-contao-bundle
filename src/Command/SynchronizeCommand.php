<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle\Command;

use Derhaeuptling\RegiondoBundle\Exception\SynchronizerException;
use Derhaeuptling\RegiondoBundle\Synchronizer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SynchronizeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('regiondo:sync')
            ->setDescription('Synchronize the Regiondo data.')
            ->addArgument('what', InputArgument::OPTIONAL, 'What to synchronize. Possible values: calendars, products, reviews.')
            ->addOption('calendars', null, InputOption::VALUE_REQUIRED, 'Comma separated calendar IDs')
            ->addOption('reviews', null, InputOption::VALUE_REQUIRED, 'Comma separated review content element IDs')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->getContainer()->get('contao.framework')->initialize();
        $synchronizer = $this->getContainer()->get(Synchronizer::class);
        $what = $input->getArgument('what');

        // Synchronize all if no explicit argument was provided
        if (!$what) {
            $this->synchronizeProducts($synchronizer, $io);
            $this->synchronizeReviews($synchronizer, $io, $input->getOption('reviews'));
            $this->synchronizeCalendars($synchronizer, $io, $input->getOption('calendars'));
        } else {
            switch ($what) {
                case 'calendars':
                    $this->synchronizeCalendars($synchronizer, $io, $input->getOption('calendars'));
                    break;
                case 'products':
                    $this->synchronizeProducts($synchronizer, $io);
                    break;
                case 'reviews':
                    $this->synchronizeReviews($synchronizer, $io, $input->getOption('reviews'));
                    break;
                default:
                    $io->error(\sprintf('The "%s" argument is unsupported', $what));

                    return 1;
            }
        }

        $io->success('Regiondo data synchronized successfully.');
    }

    /**
     * Synchronize the products.
     *
     * @param Synchronizer $synchronizer
     * @param SymfonyStyle $io
     */
    private function synchronizeProducts(Synchronizer $synchronizer, SymfonyStyle $io): void
    {
        $io->comment('Synchronizing products…');

        $stats = $synchronizer->synchronizeProducts();

        $table = new Table($io);
        $table->setHeaders(['Total products', 'Created products', 'Updated products', 'Obsolete products']);
        $table->addRow([\count($stats['products']), $stats['created'], $stats['updated'], $stats['obsolete']]);
        $table->render();
    }

    /**
     * Synchronize the reviews.
     *
     * @param Synchronizer $synchronizer
     * @param SymfonyStyle $io
     * @param string       $contentElementIds
     */
    private function synchronizeReviews(Synchronizer $synchronizer, SymfonyStyle $io, string $contentElementIds = null): void
    {
        $io->comment('Synchronizing reviews…');
        $contentElementIds = (null !== $contentElementIds) ? trimsplit(',', $contentElementIds) : [];

        $contentElements = $this
            ->getContainer()
            ->get('database_connection')
            ->fetchAll('SELECT id FROM tl_content WHERE type=?'.(\count($contentElementIds) > 0 ? (' AND id IN ('.\implode(',', $contentElementIds).')') : ''), ['regiondo_reviews'])
        ;

        if (0 === \count($contentElements)) {
            $io->note('There are no content elements to synchronize.');

            return;
        }

        $table = new Table($io);
        $table->setHeaders(['Content element ID', 'Reviews', 'Notes']);

        foreach ($contentElements as $contentElement) {
            try {
                $stats = $synchronizer->synchronizeReviews((int) $contentElement['id']);
            } catch (SynchronizerException $e) {
                $table->addRow([$contentElement['id'], 0, $e->getMessage()]);
                continue;
            }

            $table->addRow([$contentElement['id'], $stats['total'], '–']);
        }

        $table->render();
    }

    /**
     * Synchronize the calendars.
     *
     * @param Synchronizer $synchronizer
     * @param SymfonyStyle $io
     * @param string       $calendarIds
     */
    private function synchronizeCalendars(Synchronizer $synchronizer, SymfonyStyle $io, string $calendarIds = null): void
    {
        $io->comment('Synchronizing calendars…');
        $calendarIds = (null !== $calendarIds) ? trimsplit(',', $calendarIds) : [];

        if (0 === \count($calendars = $this->getCalendars($calendarIds))) {
            $io->note('There are no calendars to synchronize.');

            return;
        }

        $table = new Table($io);
        $table->setHeaders(['Calendar ID', 'Created events', 'Updated events', 'Deleted events', 'Notes']);

        $progressBar = new ProgressBar($io, \count($calendars));
        $progressBar->start();

        // Synchronize the calendars
        foreach ($calendars as $calendar) {
            try {
                $stats = $synchronizer->synchronizeCalendar((int) $calendar);
            } catch (SynchronizerException $e) {
                $table->addRow([$calendar, 0, 0, 0, $e->getMessage()]);
                $progressBar->advance();
                continue;
            }

            $table->addRow([$calendar, $stats['created'], $stats['updated'], $stats['deleted'], '–']);
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->writeln('');
        $table->render();
    }

    /**
     * Get the calendars.
     *
     * @param array $calendarIds
     *
     * @return array
     */
    private function getCalendars(array $calendarIds = []): array
    {
        $db = $this->getContainer()->get('database_connection');

        if (\count($calendarIds) > 0) {
            $records = $db->fetchAll('SELECT id FROM tl_calendar WHERE id IN ('.\implode(',', $calendarIds).') AND regiondo_enable=1');
        } else {
            $records = $db->fetchAll('SELECT id FROM tl_calendar WHERE regiondo_enable=1');
        }

        $ids = [];

        foreach ($records as $record) {
            $ids[] = (int) $record['id'];
        }

        return $ids;
    }
}

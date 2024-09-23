#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Helper\ProgressBar;

(new SingleCommandApplication())
    ->setName('My Benchmark Command') // Optional
    ->setVersion('1.0.0') // Optional
    ->addOption('endpoint', null, InputOption::VALUE_OPTIONAL)
    ->addOption('iterations', null, InputOption::VALUE_OPTIONAL)
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $endpoint = $input->getOption('endpoint');
        $iterations = $input->getOption('iterations');

        $helper = $this->getHelper('question');
        if (null === $endpoint) {
            $endpointQuestion = new Question('Which endpoint do you want to benchmark? ');
            $endpoint = $helper->ask($input, $output, $endpointQuestion);
        }

        if (null === $iterations) {
            $iterationQuestion = new Question('For how many iterations? ');
            $iterations = (int) $helper->ask($input, $output, $iterationQuestion);
        }

        $client = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
        ]);
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $progressBar = new ProgressBar($output, $iterations);
        $progressBar->start();

        $durations = [];
        for ($i = 0; $i < $iterations; $i++) {
            $response = $client->request('GET', $endpoint, ['headers' => [
                'Cookie' => 'laravel_session=eyJpdiI6IkFhKzF0OEtYME84Q2h6c2tlcjdWY3c9PSIsInZhbHVlIjoiUURwYThYVVNYVitObmpNaHAxVVUwMXRYanlnNzRGZUtIOEZCWEVFdThYL1h3eEovUnRhY0tXdHFVNnRCSVlIVE1ETnZNTEUyUFZOK1hPSS9MWFlibCtybkVIc28xbm16RmhCVkF2cDNCTVh4cHVmSytBc25VQmJCOUdqSzdrb2IiLCJtYWMiOiJlNzk1NzdmZWYwZGI4MTJlYTYxNWUyMjI2N2RiYmNlM2Y1NTE2OTZlMTZmMDc1NjA4YTc5ZTQ2MWZiNzBkNWZjIiwidGFnIjoiIn0%3D',
            ]]);

            if ($response->getStatusCode() !== 200) {
                die('call failed');
            }

            $response->getContent();
            $durations[] = $response->getInfo()['total_time'];

            $progressBar->advance();
        }

        $progressBar->finish();

        $result = round(array_sum($durations) / count($durations) * 1000);

        $formatter = $this->getHelper('formatter');
        $formattedLine = $formatter->formatSection(
            'Result',
            $result . ' ms'
        );
        $output->writeln(PHP_EOL);
        $output->writeln($formattedLine);
        $output->writeln(PHP_EOL);

        return Command::SUCCESS;
    })
    ->run();

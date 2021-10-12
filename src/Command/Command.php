<?php

namespace Kucbel\Console\Command;

use Nette\NotImplementedException;
use Symfony;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class Command extends Symfony\Component\Console\Command\Command
{
	const
		ARG_REQUIRED	= InputArgument::REQUIRED,
		ARG_OPTIONAL	= InputArgument::OPTIONAL,
		ARG_VARIADIC	= InputArgument::IS_ARRAY,

		OPT_BOOLEAN		= InputOption::VALUE_NONE,
		OPT_REQUIRED	= InputOption::VALUE_REQUIRED,
		OPT_OPTIONAL	= InputOption::VALUE_OPTIONAL,
		OPT_MULTIPLE	= InputOption::VALUE_IS_ARRAY,
		OPT_NEGATIVE	= InputOption::VALUE_NEGATABLE;

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) : int
	{
		throw new NotImplementedException;
	}
}
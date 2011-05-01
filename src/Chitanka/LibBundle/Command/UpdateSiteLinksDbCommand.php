<?php

namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Chitanka\LibBundle\Entity\Site;
use Chitanka\LibBundle\Legacy\Legacy;

class UpdateSiteLinksDbCommand extends CommonDbCommand
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('db:update-sites')
			->setDescription('Update links to external sites')
			->setHelp(<<<EOT
The <info>db:update-sites</info> command reads data from the wiki and updates the links to external sites.
EOT
        );
		;
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 *
	 * @return integer 0 if everything went fine, or an error code
	 *
	 * @throws \LogicException When this abstract class is not implemented
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->get('doctrine.orm.default_entity_manager');
		$this->updateLinks($this->fetchWikiContent($output), $output, $em);
		$output->writeln('Done.');
	}


	/**
	* @RawSql
	*/
	protected function updateLinks($wikiContent, OutputInterface $output, $em)
	{
		$linksData = $this->extractLinkData($wikiContent);
		if ($linksData) {
			$output->writeln('Updating site links...');
			$em->getConnection()->executeUpdate('TRUNCATE TABLE site');
			foreach ($linksData as $linkData) {
				$site = new Site;
				$site->setUrl($linkData[1]);
				$site->setName($linkData[2]);
				$site->setDescription(strip_tags($linkData[3]));
				$em->persist($site);
			}
			$em->flush();
		}
	}

	protected function fetchWikiContent(OutputInterface $output)
	{
		$output->writeln('Fetching wiki content...');
		return Legacy::getMwContent('http://wiki.chitanka.info/Links', 0);
	}

	protected function extractLinkData($wikiContent)
	{
		if (preg_match_all('|class="external text" href="([^"]+)">([^<]+)</a>(.*)|', $wikiContent, $matches, PREG_SET_ORDER)) {
			return $matches;
		}

		return false;
	}
}

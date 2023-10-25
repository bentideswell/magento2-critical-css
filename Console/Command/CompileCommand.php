<?php
/**
 * @url FishPig.com
 */
declare(strict_types=1);

namespace FishPig\CriticalCss\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class CompileCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     *
     */
    const FILE = 'file';

    /**
     *
     */
    const THEME = 'theme';

    /**
     *
     */
    const AREA = 'area';

    /**
     *
     */
    private $appState = null;

    /**
     *
     */
    private $dataProvider = null;

    /**
     *
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \FishPig\CriticalCss\App\DataProvider $dataProvider,
        string $name = null
    ) {
        $this->appState = $appState;
        $this->dataProvider = $dataProvider;
        parent::__construct($name);
    }

    /**
     * @return $this
     */
    protected function configure()
    {
        $this->setName('fishpig:criticalcss:compile');
        $this->setDescription('Get the critical CSS from a specific file');
        $this->setDefinition([
            new InputOption(self::FILE, null, InputOption::VALUE_REQUIRED),
            new InputOption(self::THEME, null, InputOption::VALUE_REQUIRED),
            new InputOption(self::AREA, null, InputOption::VALUE_REQUIRED)
        ]);
        return parent::configure();
    }

    /**
     * @param  InputInterface $input
     * @param  OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set the area code
        $area = $input->getOption(self::AREA) ?: 'frontend';
        $this->appState->setAreaCode($area);

        if (!($file = $input->getOption(self::FILE))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You did not specify a filename (eg. --%s=css/styles.css)',
                    self::FILE
                )
            );
        } elseif (!($theme = $input->getOption(self::THEME))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You did not specify a filename (eg. --%s=Magento/luma)',
                    self::THEME
                )
            );
        }

        $output->write(
            $this->dataProvider->getCriticalCss($file, $theme, $area)
        );

        return parent::SUCCESS;
    }
}

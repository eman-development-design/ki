<?php

namespace App\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class PageBuilderService
{
    private string $templateRoot = 'templates/pages/';

    public function __construct(
        #[Autowire(param: 'app.output_dir')]
        private readonly string $outputDir,
        private readonly Environment $twig,
    ) {
    }

    /**
     * Builds the pages by processing templates and generating output files.
     *
     * @param array<string, mixed> $tags array of tags to be used when creating pages
     *
     * @return void
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function buildPages(array $tags): void
    {
        $pages = [];

        $this->getPageList($this->templateRoot, $pages);

        foreach ($pages as $page) {
            $info = pathinfo($page);
            $path = str_replace(dirname($this->templateRoot).DIRECTORY_SEPARATOR, '/', $info['dirname']);
            $pagePath = str_replace(rtrim($this->templateRoot, DIRECTORY_SEPARATOR), '', $info['dirname']);
            $pageTagIdentifier = $pagePath.DIRECTORY_SEPARATOR.$info['filename'];
            $pageTags = $this->buildTags($tags, $pageTagIdentifier);

            if ($info['filename'] === 'index') {
                $outputPath = $this->outputDir.$pagePath;
            } else {
                $outputPath = $this->outputDir.$pagePath.DIRECTORY_SEPARATOR.$info['filename'];
            }

            if (!is_dir($outputPath) && !@mkdir($outputPath, 0755, true)
                && !is_dir($outputPath)) {
                throw new RuntimeException(sprintf('Directory `%s` was not created', $outputPath));
            }

            $this->createPage($path.DIRECTORY_SEPARATOR.$info['basename'], $outputPath, $pageTags);
        }
    }

    /**
     * Generates and saves a page from a template using provided tags.
     *
     * @param string   $templatePath path to the template file
     * @param string   $outputPath   directory where the generated page will be saved
     * @param string[] $tags         key-value pairs used to populate the template
     *
     * @return void
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function createPage(string $templatePath, string $outputPath, array $tags): void
    {
        $html = $this->twig->render($templatePath, $tags);

        file_put_contents($outputPath.DIRECTORY_SEPARATOR.'index.html', $html);
    }

    /**
     * Builds a list of tags for a specific page by merging global tags with page-specific tags.
     *
     * @param array<string, mixed> $tags the array containing global and page-specific tags
     * @param string               $page the name of the page to retrieve tags for
     *
     * @return array<string, mixed> the merged list of tags for the page
     */
    private function buildTags(array $tags, string $page): array
    {
        if (array_key_exists($page, $tags['pages'])) {
            return array_merge($tags['global'], $tags['pages'][$page]);
        }

        return $tags['global'];
    }

    /**
     * Compiles a list of files & folders.
     *
     * @param string   $dir
     * @param string[] $results
     *
     * @return void
     */
    private function getPageList(string $dir = '', array &$results = []): void
    {
        foreach (array_diff(scandir($dir, SCANDIR_SORT_NONE), ['..', '.']) as $path) {
            if (is_dir($dir.$path.'/')) {
                $this->getPageList($dir.$path.'/', $results);
            } else {
                $results[] = $dir.$path;
            }
        }
    }
}

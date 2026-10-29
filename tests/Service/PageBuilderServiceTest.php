<?php

namespace App\Tests\Service;

use App\Service\PageBuilderService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Twig\Environment;

#[CoversClass(PageBuilderService::class)]
class PageBuilderServiceTest extends TestCase
{
    private string $outputDir;
    private string $templatesDir;
    private Environment $twig;
    private PageBuilderService $pageBuilderService;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir().'/page_builder_test_output';
        $this->templatesDir = sys_get_temp_dir().'/page_builder_test_templates';
        $this->twig = $this->createMock(Environment::class);
        $this->pageBuilderService = new PageBuilderService($this->outputDir, $this->twig);

        // Ensure clean directories
        if (is_dir($this->outputDir)) {
            $this->deleteDirectory($this->outputDir);
        }

        if (is_dir($this->templatesDir)) {
            $this->deleteDirectory($this->templatesDir);
        }

        mkdir($this->outputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputDir)) {
            $this->deleteDirectory($this->outputDir);
        }

        if (is_dir($this->templatesDir)) {
            $this->deleteDirectory($this->templatesDir);
        }
    }

    public function testBuildPagesGeneratesOutputForTemplates(): void
    {
        $tags = [
            'global' => ['siteName' => 'Test Site'],
            'pages' => [
                '/about' => ['title' => 'About Page'],
            ],
        ];

        mkdir($this->templatesDir.'/pages', 0755, true);
        file_put_contents($this->templatesDir.'/pages/index.twig', '<h1>{{ siteName }}</h1>');
        file_put_contents($this->templatesDir.'/pages/about.twig', '<h1>{{ title }}</h1>');

        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturnMap([
                ['/pages/index.twig', ['siteName' => 'Test Site'], '<h1>Test Site</h1>'],
                ['/pages/about.twig', ['siteName' => 'Test Site', 'title' => 'About Page'], '<h1>About Page</h1>'],
            ]);

        $templateRootProperty = new ReflectionProperty(PageBuilderService::class, 'templateRoot');
        $templateRootProperty->setValue($this->pageBuilderService, $this->templatesDir.'/pages/');

        $this->pageBuilderService->buildPages($tags);

        $this->assertFileExists($this->outputDir.'/index.html');
        $this->assertFileExists($this->outputDir.'/about/index.html');
        $this->assertStringEqualsFile($this->outputDir.'/index.html', '<h1>Test Site</h1>');
        $this->assertStringEqualsFile($this->outputDir.'/about/index.html', '<h1>About Page</h1>');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testBuildPagesThrowsExceptionWhenDirectoryCannotBeCreated(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Directory `.*` was not created/');

        $tags = [
            'global' => ['siteName' => 'Test Site'],
            'pages' => [],
        ];

        $unwritableDir = '/root/inaccessible';
        $pageBuilderService = new PageBuilderService($unwritableDir, $this->twig);

        $pageBuilderService->buildPages($tags);
    }

    public function testBuildPagesHandlesTwigRenderExceptions(): void
    {
        $this->expectException(RuntimeException::class);
        $this->twig->expects($this->once())
            ->method('render')
            ->willThrowException(new RuntimeException('Twig Rendering Error'));

        $tags = [
            'global' => [],
            'pages' => [],
        ];

        mkdir($this->templatesDir.'/pages', 0755, true);
        file_put_contents($this->templatesDir.'/pages/index.twig', '<h1>{{ siteName }}</h1>');

        $templateRootProperty = new ReflectionProperty(PageBuilderService::class, 'templateRoot');
        $templateRootProperty->setValue($this->pageBuilderService, $this->templatesDir.'/pages/');

        $this->pageBuilderService->buildPages($tags);
    }

    private function deleteDirectory(string $directory): void
    {
        foreach (array_diff(scandir($directory), ['.', '..']) as $file) {
            $path = $directory.DIRECTORY_SEPARATOR.$file;

            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}

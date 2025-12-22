<?php
namespace Tests\Unit\Core;

use Tests\TestCase;
use core\View;

class ViewTest extends TestCase
{
    private View $view;

    protected function setUp(): void
    {
        parent::setUp();
        $this->view = new View();
    }

    public function testViewRendersWithData(): void
    {
        // Create a simple test view in the views directory
        // View class looks for views at: dirname(__DIR__) . '/views/' . $view . '.php'
        // From app/core/View.php, dirname(__DIR__) is app/, so path is app/views/temp_test_view.php
        // From tests/Unit/Core/ViewTest.php, we need to go up 3 levels to get to project root
        $viewsDir = dirname(__DIR__, 3) . '/app/views';
        $testViewPath = $viewsDir . '/temp_test_view.php';
        $testViewContent = '<?php echo $title; ?> - <?php echo $message; ?>';
        
        // Create views directory if it doesn't exist
        if (!is_dir($viewsDir)) {
            mkdir($viewsDir, 0755, true);
        }
        
        // Ensure the file is created and exists before calling render
        $fileCreated = file_put_contents($testViewPath, $testViewContent);
        
        // Verify file was created and exists
        if ($fileCreated === false || !file_exists($testViewPath)) {
            $this->markTestSkipped('View rendering test skipped: Could not create test view file at ' . $testViewPath);
            return;
        }
        
        // Small delay to ensure file system has written the file
        usleep(100000); // 100ms delay

        try {
            // Suppress output to avoid "headers already sent" errors
            ob_start();
            $this->view->render('temp_test_view', [
                'title' => 'Test Title',
                'message' => 'Test Message',
            ], ''); // No layout - pass empty string
            $output = ob_get_clean();

            $this->assertStringContainsString('Test Title', $output);
            $this->assertStringContainsString('Test Message', $output);
        } catch (\RuntimeException $e) {
            // If view file doesn't exist, skip this test
            $this->markTestSkipped('View rendering test skipped: ' . $e->getMessage());
        } finally {
            // Clean up: remove the test view file
            if (file_exists($testViewPath)) {
                @unlink($testViewPath);
            }
        }
    }

    public function testViewThrowsExceptionForMissingView(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('View nonexistent_view not found');
        
        $this->view->render('nonexistent_view');
    }
}

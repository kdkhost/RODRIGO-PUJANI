<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class DjenLayoutConsistencyTest extends TestCase
{
    public function test_djen_index_uses_the_standard_admin_card_spacing(): void
    {
        $view = file_get_contents(resource_path('views/admin/djen-publications/index.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('app-content-header admin-page-hero', $view);
        $this->assertGreaterThanOrEqual(5, substr_count($view, 'admin-table-card'));
        $this->assertGreaterThanOrEqual(2, substr_count($view, 'admin-card-flow'));
        $this->assertSame(2, substr_count($view, 'card-body table-responsive p-0'));
    }
}

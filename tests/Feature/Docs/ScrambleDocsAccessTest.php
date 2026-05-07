<?php

namespace Tests\Feature\Docs;

use Tests\TestCase;

class ScrambleDocsAccessTest extends TestCase
{
    public function test_docs_api_is_accessible_in_non_local_environment(): void
    {
        config()->set('app.env', 'production');

        $response = $this->get('/docs/api');

        $response->assertOk();
    }

    public function test_docs_api_json_is_accessible_in_non_local_environment(): void
    {
        config()->set('app.env', 'production');

        $response = $this->get('/docs/api.json');

        $response->assertOk();
    }
}

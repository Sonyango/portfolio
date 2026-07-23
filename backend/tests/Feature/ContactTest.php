<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_submit_contact_form(): void
    {
        $response = $this->postJson('/api/contact', [
            'name'      => 'John Doe',
            'email'     => 'john@example.com',
            'subject'   => 'Project Inquiry',
            'message'   => 'I would like to discuss a project with you.',
        ]);
        $response->assertStatus(200)
                ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('contact_messages', [
            'email'     => 'john@example.com',
            'subject'   => 'Project Inquiry',
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->postJson('/api/contact', []);
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name', 'email', 'subject', 'message'
                ]);
    }

    public function test_contact_form_validates_email_format(): void
    {
        $response = $this->postJson('/api/contact', [
            'name'      => 'John',
            'email'     => 'not-an-email',
            'subject'   => 'Test',
            'message'   => 'Test message here.',
        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_contact_form_validates_message_max_length(): void
    {
        $response = $this->postJson('/api/contact', [
            'name'      => 'John',
            'email'     => 'john@example.com',
            'subject'   => 'Test',
            'message'   => str_repeat('a', 2001),
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);
    }
}

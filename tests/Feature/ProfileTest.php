<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('auth.user.avatar_tipo', 'inicial')
                ->where('esAdmin', false));
    }

    public function test_profile_lists_granted_permissions_by_section(): void
    {
        $user = User::factory()->create();
        $user->permissions()->create(['modulo' => 'especies', 'ver' => true, 'crear' => true]);
        $user->permisosGranulares()->create(['clave' => 'facturacion.agregar_facturas']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->has('secciones', 2)
                ->where('secciones.0.etiqueta', 'Notas de pago')
                ->where('secciones.0.granulares', ['Agregar notas de pago'])
                ->where('secciones.1.etiqueta', 'Catálogos')
                ->where('secciones.1.modulos', [['etiqueta' => 'Especies', 'acciones' => ['Ver', 'Crear']]]));
    }

    public function test_account_can_no_longer_be_deleted_or_edited_from_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/profile')->assertMethodNotAllowed();
        $this->actingAs($user)->patch('/profile', ['name' => 'Otro'])->assertMethodNotAllowed();

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_choose_initials_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', ['avatar_tipo' => 'iniciales'])
            ->assertSessionHasNoErrors();

        $this->assertSame('iniciales', $user->fresh()->avatar_tipo);
    }

    public function test_user_can_upload_a_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', [
                'avatar_tipo' => 'foto',
                'foto' => UploadedFile::fake()->image('yo.jpg', 200, 200),
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('foto', $user->avatar_tipo);
        Storage::disk('local')->assertExists($user->avatar_path);
        $this->assertNotNull($user->avatar_url);

        $this->actingAs($user)->get($user->avatar_url)->assertOk();
    }

    public function test_photo_is_required_when_choosing_photo_without_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/avatar', ['avatar_tipo' => 'foto'])
            ->assertSessionHasErrors('foto');

        $this->assertSame('inicial', $user->fresh()->avatar_tipo);
    }
}

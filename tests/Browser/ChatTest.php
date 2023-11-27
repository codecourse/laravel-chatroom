<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\ChatPage;
use Tests\DuskTestCase;

class ChatTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @test A user can send a message
     *
     * @return void
     */
    public function a_user_can_send_a_message()
    {
        $user = factory(User::class)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new ChatPage)
                ->typeMessage('Hi there')
                ->sendMessage()
                ->assertInputValue('@body', '')
                ->with('@chatMessages', function ($messages) use ($user) {
                    $messages->assertSee('Hi there')
                        ->assertSee($user->name);
                })
                ->logout();
        });
    }

    /**
     * @test A user can send a multiline message
     *
     * @return void
     */
    public function a_user_can_send_a_multiline_message()
    {
        $user = factory(User::class)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new ChatPage)
                ->typeMessage('Test message')
                ->keys('@body', '{shift}', '{enter}')
                ->append('@body', 'New line')
                ->sendMessage()
                ->assertSeeIn('@chatMessages', "Test message\nNew line")
                ->logout();
        });
    }

    /**
     * @test A user can't send an empty message
     *
     * @return void
     */
    public function a_user_cant_send_an_empty_message()
    {
        $user = factory(User::class)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new ChatPage);

            foreach (['      ', ''] as $empty) {
                $browser->typeMessage($empty)
                    ->sendMessage()
                    ->assertDontSeeIn('@chatMessages', $user->name);
            }

            $browser->keys('@body', '{shift}', '{enter}')
                ->keys('@body', '{shift}', '{enter}')
                ->sendMessage()
                ->assertDontSeeIn('@chatMessages', $user->name)
                ->logout();
        });
    }

    /**
     * @test Messages are ordered by latest first
     *
     * @return void
     */
    public function messages_are_ordered_by_latest_first()
    {
        $user = factory(User::class)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new ChatPage);

            foreach (['One', 'Two', 'Three'] as $message) {
                $browser->typeMessage($message)
                    ->sendMessage()
                    ->waitFor('@firstChatMessage')
                    ->assertSeeIn('@firstChatMessage', $message);
            }

            $browser->logout();
        });
    }

    /**
     * @test A user's message is highlighted as their own
     *
     * @return void
     */
    public function a_users_message_is_highlighted_as_their_own()
    {
        $user = factory(User::class)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new ChatPage)
                ->typeMessage('My message')
                ->sendMessage()
                ->waitFor('@ownMessage')
                ->with('@ownMessage', function ($message) use ($user) {
                    $message->assertSee('My message')
                        ->assertSee($user->name);
                })
                ->logout();
        });
    }
}

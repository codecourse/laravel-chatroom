<?php

namespace Tests\Browser;

use App\Models\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\ChatPage;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ChatRealtimeTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @test A user can send a message
     *
     * @return void
     */
    public function a_user_can_see_messages_from_other_users()
    {
        $users = factory(User::class, 3)->create();

        $this->browse(function ($browserOne, $browserTwo, $browserThree) use ($users) {
            $browsers = [$browserOne, $browserTwo, $browserThree];

            foreach ($browsers as $index => $browser) {
                $browser->loginAs($users->get($index))
                    ->visit(new ChatPage);
            }

            $browserOne->typeMessage('Hi there')
                ->sendMessage();

            foreach (array_slice($browsers, 1, 2) as $index => $browser) {
                $browser->waitFor('@firstChatMessage')
                    ->with('@chatMessages', function ($messages) use ($users) {
                        $messages->assertSee('Hi there')
                            ->assertSee($users->get(0)->name)
                            ->assertMissing('@ownMessage');
                    });
            }

            $browserThree->typeMessage('Oh hai')
                ->sendMessage();

            foreach (array_slice($browsers, 0, 1) as $index => $browser) {
                $browser->waitForText('Oh hai')
                    ->with('@chatMessages', function ($messages) use ($users) {
                        $messages->assertSee('Oh hai')
                            ->assertSee($users->get(2)->name);
                    });
            }
        });
    }

    /**
     * @test Users are added to the online list when joining
     *
     * @return void
     */
    public function users_are_added_to_the_online_list_when_joining()
    {
        $users = factory(User::class, 2)->create();

        $this->browse(function ($browserOne, $browserTwo) use ($users) {
            $browserOne->loginAs($users->get(0))
                ->visit(new ChatPage)
                ->with('@onlineList', function ($online) use ($users) {
                    $online->waitForText($users->get(0)->name)
                        ->assertSee($users->get(0)->name)
                        ->assertSee('1 user online');
                });

            $browserTwo->loginAs($users->get(1))
                ->visit(new ChatPage)
                ->with('@onlineList', function ($online) use ($users) {
                    $online->waitForText($users->get(1)->name)
                        ->assertSee($users->get(1)->name)
                        ->assertSee('2 users online');
                });
        });
    }

    /**
     * @test Users are removed from the online list when leaving
     *
     * @return void
     */
    public function users_are_removed_from_the_online_list_when_leaving()
    {
        $users = factory(User::class, 2)->create();

        $this->browse(function ($browserOne, $browserTwo) use ($users) {
            $browserOne->loginAs($users->get(0))
                ->visit(new ChatPage);

            $browserTwo->loginAs($users->get(1))
                ->visit(new ChatPage);

            $browserOne->with('@onlineList', function ($online) use ($users) {
                $online->waitForText($users->get(1)->name)
                    ->assertSee($users->get(1)->name)
                    ->assertSee('2 users online');
            });

            $browserTwo->quit();

            $browserOne->with('@onlineList', function ($online) use ($users) {
                $online->pause(1000)
                    ->assertDontSee($users->get(1)->name)
                    ->assertSee('1 user online');
            });
        });
    }
}

<?php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Tests\TestCase;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        cache()->clear();
        $this->artisan('migrate:fresh');
        Product::create([
            'name' => 'Sample Product',
            'description' => 'This is a sample product description.',
            'price' => 100.00, 
            'stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Accept', 'application/json');
        config(['app.url' => 'http://localhost:8000']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function success_two_users_try_to_checkout_guzzel_pool_method(){
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $tokenUser1 = $user1->createToken('TestToken1')->plainTextToken;
        $tokenUser2 = $user2->createToken('TestToken2')->plainTextToken;

        $responses = Http::pool(fn (Pool $pool) => [
            $pool->acceptJson()->withToken($tokenUser1)->post('http://localhost:8000/v1/checkout', ['product_id' => 1,'quantity' => 1,]),
            $pool->acceptJson()->withToken($tokenUser2)->post('http://localhost:8000/v1/checkout', ['product_id' => 1,'quantity' => 1,]),
        ]);

        $responseUser1 = $responses[0];
        $responseUser2 = $responses[1];

        $statusUser1 = $responseUser1->getStatusCode();
        $statusUser2 = $responseUser2->getStatusCode();

        $this->assertTrue(
            ($statusUser1 === 200 && $statusUser2 === 400) || 
            ($statusUser1 === 400 && $statusUser2 === 200),
            'Only one user should succeed in checkout when stock is limited.'
        );

        $this->assertDatabaseHas('products', [
            'id' => 1,
            'stock' => 0,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function success_two_users_try_to_checkout_guzzel_async_method() {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
    
        $tokenUser1 = $user1->createToken('TestToken1')->plainTextToken;
        $tokenUser2 = $user2->createToken('TestToken2')->plainTextToken;
    
        $checkoutData = [
            'product_id' => 1,
            'quantity' => 1,
        ];
    
        $headersUser1 = [
            'Authorization' => 'Bearer ' . $tokenUser1,
            'Accept' => 'application/json',
        ];
    
        $headersUser2 = [
            'Authorization' => 'Bearer ' . $tokenUser2,
            'Accept' => 'application/json',
        ];
    
        $client = new Client([
            'base_uri' => config('app.url'),
        ]);
    
        $promises = [
            'user1' => $client->postAsync('/v1/checkout', [
                'json' => $checkoutData,
                'headers' => $headersUser1,
            ])->then(
                function ($response) {
                    return $response;
                },
                function ($exception) {
                    return $exception->getResponse();
                }
            ),
            'user2' => $client->postAsync('/v1/checkout', [
                'json' => $checkoutData,
                'headers' => $headersUser2,
            ])->then(
                function ($response) {
                    return $response;
                },
                function ($exception) {
                    return $exception->getResponse();
                }
            ),
        ];
    
        $responses = Promise\Utils::unwrap($promises);
    
        $responseUser1 = $responses['user1'];
        $responseUser2 = $responses['user2'];
    
        $statusUser1 = $responseUser1->getStatusCode();
        $statusUser2 = $responseUser2->getStatusCode();
    
        $this->assertTrue(
            ($statusUser1 === 200 && $statusUser2 === 400) ||
            ($statusUser1 === 400 && $statusUser2 === 200),
            'Only one user should succeed in checkout when stock is limited.'
        );
    
        $this->assertDatabaseHas('products', [
            'id' => 1,
            'stock' => 0,
        ]);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function success_ten_users_try_to_checkout_guzzel_pool_method() {
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create();
            $token = $user->createToken("TestToken$i")->plainTextToken;
            $users[] = [
                'token' => $token,
                'user' => $user,
            ];
        }
        // Menggunakan Http::pool untuk menjalankan request secara paralel
        $responses = Http::pool(function (Pool $pool) use ($users) {
            $requests = [];
            
            foreach ($users as $userData) {
                $requests[] = $pool->acceptJson()->withToken($userData['token'])->post('http://localhost:8000/v1/checkout', [
                    'product_id' => 1,
                    'quantity' => 1,
                ]);
            }
            
            return $requests;
        });

        $statuses = array_map(function ($response) {
            return $response->getStatusCode();
        }, $responses);
        
        $successCount = count(array_filter($statuses, function ($status) {
            return $status === 200;
        }));

        $this->assertTrue(
            $successCount === 1,
            'Only one user should succeed in checkout when stock is limited.'
        );

        $this->assertDatabaseHas('products', [
            'id' => 1,
            'stock' => 0,
        ]);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function success_ten_users_try_to_checkout_guzzel_async_method() {
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create();
            $token = $user->createToken("TestToken$i")->plainTextToken;

            $users[] = [
                'user' => $user,
                'token' => $token,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ];
        }

        // Data checkout yang sama untuk semua user
        $checkoutData = [
            'product_id' => 1,
            'quantity' => 1,
        ];
    
        $client = new Client([
            'base_uri' => config('app.url'),
        ]);
    
        $promises = [];
        foreach ($users as $index => $userData) {
            $promises["user{$index}"] = $client->postAsync('/v1/checkout', [
                'json' => $checkoutData,
                'headers' => $userData['headers'],
            ])->then(
                function ($response) {
                    return $response;
                },
                function ($exception) {
                    return $exception->getResponse();
                }
            );
        }

        // Tunggu semua promises selesai
        $responses = Promise\Utils::unwrap($promises);

        $successfulCheckouts = 0;
        $failedCheckouts = 0;
    
        foreach ($responses as $index => $response) {
            $status = $response->getStatusCode();
    
            if ($status === 200) {
                $successfulCheckouts++;
            } elseif ($status === 400) {
                $failedCheckouts++;
            }
        }
    
        $this->assertEquals(1, $successfulCheckouts, 'Only one user should succeed in checkout when stock is limited.');
        $this->assertEquals(9, $failedCheckouts, 'Nine users should fail due to limited stock.');

    
        $this->assertDatabaseHas('products', [
            'id' => 1,
            'stock' => 0,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function success_hundred_users_try_to_checkout_guzzel_async_method() {
        $users = [];
        for ($i = 1; $i <= 100; $i++) {
            $user = User::factory()->create();
            $token = $user->createToken("TestToken$i")->plainTextToken;

            $users[] = [
                'user' => $user,
                'token' => $token,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ];
        }

        // Data checkout yang sama untuk semua user
        $checkoutData = [
            'product_id' => 1,
            'quantity' => 1,
        ];

        $client = new Client([
            'base_uri' => config('app.url'),
        ]);

        $promises = [];
        foreach ($users as $index => $userData) {
            $promises["user{$index}"] = $client->postAsync('/v1/checkout', [
                'json' => $checkoutData,
                'headers' => $userData['headers'],
            ])->then(
                function ($response) {
                    return $response;
                },
                function ($exception) {
                    return $exception->getResponse();
                }
            );
        }

        // Tunggu semua promises selesai
        $responses = Promise\Utils::unwrap($promises);

        $successfulCheckouts = 0;
        $failedCheckouts = 0;

        foreach ($responses as $index => $response) {
            $status = $response->getStatusCode();

            if ($status === 200) {
                $successfulCheckouts++;
            } elseif ($status === 400) {
                $failedCheckouts++;
            }
        }

        // Hanya satu user yang harus berhasil checkout, 99 lainnya harus gagal
        $this->assertEquals(1, $successfulCheckouts, 'Only one user should succeed in checkout when stock is limited.');
        $this->assertEquals(99, $failedCheckouts, 'Ninety-nine users should fail due to limited stock.');

        // Verifikasi stok produk menjadi 0 setelah satu checkout sukses
        $this->assertDatabaseHas('products', [
            'id' => 1,
            'stock' => 0,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function success_hundred_users_try_to_checkout_guzzel_pool_method() {
        $users = [];
        for ($i = 1; $i <= 100; $i++) {
            $user = User::factory()->create();
            $token = $user->createToken("TestToken$i")->plainTextToken;
            $users[] = [
                'token' => $token,
                'user' => $user,
            ];
        }

        // Menggunakan Http::pool untuk menjalankan request secara paralel untuk 100 user
        $responses = Http::pool(function (Pool $pool) use ($users) {
            $requests = [];
            
            foreach ($users as $userData) {
                $requests[] = $pool->acceptJson()->withToken($userData['token'])->post('http://localhost:8000/v1/checkout', [
                    'product_id' => 1,
                    'quantity' => 1,
                ]);
            }
            
            return $requests;
        });

        $statuses = array_map(function ($response) {
            return $response->getStatusCode();
        }, $responses);
        
        $successCount = count(array_filter($statuses, function ($status) {
            return $status === 200;
        }));
        $this->assertTrue(
            $successCount === 1,
            'Only one user should succeed in checkout when stock is limited.'
        );
        $this->assertDatabaseHas('products', [
            'id' => 1,
            'stock' => 0,
        ]);
    }


    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Core\JWK;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker;
use Jose\Component\KeyManagement\JWKFactory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

class JWT extends Model
{
    use HasFactory;

    protected $table = 'refresh_tokens';
    protected $primaryKey = 'id_token';
    protected $fillable = [
        'id_user',
        'token',
        'expires',
        'device',
    ];
    protected $casts = [
        'expires' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    static $jwt_provider = "Lara Auth";
    static $jwt_secret = "r5hKyXow7l?+)y7HI-M(S,NfJIC>iG?ttJ0,DrL48pLEjYO3cipIJXbUy|nyQ7sx-B;2WtO0bxVQ,BOuL|1E,4gq_-2iKRJ)|T0prBuN8mWGstvIQSrp?UdcD3APF|nK";
    // static $jwt_default_session = 60; //полчаса
    // static $jwt_remember_session =2592000; #30 дней

    public function user()
    {
        return $this->belongsTo(User::class,'id_user');
    }

    public function remember()
    {
        $issuedAt = strtotime($this->updated_at);
        $expiresAt = strtotime($this->expires);
        $allowedTimeDrift = 60;
        return (env('JWT_REMEMBER_SESSION')-$allowedTimeDrift<=$expiresAt-$issuedAt);
    }
    // Returns generated accessToken which contains info about user_id and user_role
    public static function create_accessToken($id_user,$role,$timestamp)
    {
        // The algorithm manager with the HS256 algorithm.
        $algorithmManager = new AlgorithmManager([new HS256(),]);

        // We instantiate our JWS Builder.
        $jwsBuilder = new JWSBuilder($algorithmManager);

        // The payload we want to sign. The payload MUST be a string hence we use our JSON Converter.
        $payload = json_encode([
            'iat' => $timestamp,                            // token was generated at time
            'nbf' => $timestamp,                            // token invalid before time
            'exp' => $timestamp + env('JWT_LIFETIME',600),  // token invalid after time
            'iss' => JWT::$jwt_provider,              // token provider
            'aud' => 'Odin Labs',                           // token receiver application
            'id_user' => $id_user,
            'role' => $role,
        ]);

        $jwk = JWKFactory::createFromSecret(
            JWT::$jwt_secret,       // The shared secret
            [                      // Optional additional members
                'alg' => 'HS256',
                'use' => 'sig'
            ]
        );

        $jws = $jwsBuilder
            ->create()                               // We want to create a new JWS
            ->withPayload($payload)                  // We set the payload
            ->addSignature($jwk, ['alg' => 'HS256']) // We add a signature with a simple protected header
            ->build();                               // We build it

        // return $jws;


        $serializer = new CompactSerializer(); // The serializer

        $token = $serializer->serialize($jws, 0); // We serialize the signature at index 0 (we only have one signature).

        return $token;
    }

    public static function create_refreshToken($id_user,$remember,$device, $timestamp)
    {

        if ($remember){
            $expires = $timestamp + env('JWT_REMEMBER_SESSION');
        }else{
            $expires = $timestamp + env('JWT_DEFAULT_SESSION');
        }

        do {
            $refresh_token = Str::random(60);
        } while (JWT::find($refresh_token) !== null);

        JWT::create([
            'id_user' => $id_user,
            'token' => hash('sha256', $refresh_token),
            'expires' => date('Y-m-d H:i:s', $expires),
            'device' => json_encode($device),
        ]);

        return $refresh_token;
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var id_user integer
     * @var role integer
     * @var remember boolean
     * @var device json
     */
    public static function generate($id_user,$role,$remember,$device)
    {
        $timestamp = time();
        return [
            'accessToken'=>JWT::create_accessToken($id_user,$role,$timestamp),
            'accessTokenExpired'=>$timestamp + env('JWT_LIFETIME'),
            'refreshToken'=>JWT::create_refreshToken($id_user,$remember,$device,$timestamp),
            'remember'=>$remember,
        ];
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var refresh_token string - current refreshToken
     * @var role integer
     * @var remember boolean
     * @var device json
     * @return array contains accessToken, accessTokenExpired, refreshToken
     *
     * @throws AuthorizationException 'REFRESH_INVALID' and 'REFRESH_EXPIRED'
     */
    public static function regenerate($refresh_token,$device)
    {
        // Get token from DB
        $token = JWT::find($refresh_token);

        // Freeze timespamp
        $timestamp = time();

        // refreshToken not found or detected another device
        if (!$token){
            throw new AuthorizationException("REFRESH_INVALID");
        }
        if (json_encode($device)!==$token->device){
            throw new AuthorizationException("REFRESH_INVALID");
        }

        // check if refreshToken expired
        if (strtotime($token->expires)<$timestamp){
            $token->delete();
            throw new AuthorizationException("REFRESH_EXPIRED");
        }

        // Get user_info from token foreign
        $id_user = $token->user->id_user;
        $role = $token->user->role;

        // check if rememberMe was selected
        if ($token->remember()){
            $expires = $timestamp + env('JWT_REMEMBER_SESSION');
        }else{
            $expires = $timestamp + env('JWT_DEFAULT_SESSION');
        }
        // throw new AuthorizationException($token->remember());

        do {
            $new_refresh_token = Str::random(60);
        } while (JWT::find($new_refresh_token) !== null);

        $token->token = hash('sha256', $new_refresh_token);
        $token->expires = date('Y-m-d H:i:s', $expires);
        $token->save();

        return [
            'accessToken'=>JWT::create_accessToken($id_user,$role,$timestamp),
            'accessTokenExpires'=>$timestamp + env('JWT_LIFETIME'),
            'refreshToken'=>$new_refresh_token,
            'remember'=>$token->remember(),
        ];
    }

    /**
     * @throws Exceptions
     * @return object exception - if there is
     * @return int 0 - if token successful verified
     */
    public static function verify($token,$refresh_verifying = false)
    {
        // The serializer manager. We only use the JWS Compact Serialization Mode.
        $serializerManager = new JWSSerializerManager([
            new CompactSerializer(),
        ]);

        try {
            // We try to load the token.
            $jws = $serializerManager->unserialize($token);

            // verify header
            JWT::verify_header($jws);
            // verify sign
            if (!JWT::verify_sign($jws)){
                // Throw error for catch block
                throw new AuthorizationException("SIGN_INVALID");
            }
            // get payload
            $payload = json_decode($jws->getPayload(), true);
            // if (!$refresh_verifying){
            //     throw new Exception('fuck', 1);

            // }
            JWT::verify_payload($payload,$refresh_verifying);

        } catch (Exception $e) {
            return $e;
        }

        return 0;
    }

    /**
     * generates errors
     * @return void
     */
    private static function verify_header($jws)
    {
        $headerCheckerManager = new HeaderCheckerManager(
            [
                new AlgorithmChecker(['HS256']), // We check the header "alg" (algorithm)
            ],
            [
                new JWSTokenSupport(), // Adds JWS token type support
            ]
        );
        $headerCheckerManager->check($jws, 0);
    }

    /**
     * generates errors
     * @return bool true if sign verified, false if sign is invalid
     */
    private static function verify_sign($jws)
    {
        // The algorithm manager with the HS256 algorithm.
        $algorithmManager = new AlgorithmManager([new HS256()]);

        // We instantiate our JWS Verifier.
        $jwsVerifier = new JWSVerifier($algorithmManager);

        $jwk = JWKFactory::createFromSecret(
            JWT::$jwt_secret,       // The shared secret
            [                      // Optional additional members
                'alg' => 'HS256',
                'use' => 'sig'
            ]
        );
        // We verify the signature. This method does NOT check the header.
        // The arguments are:
        // - The JWS object,
        // - The key,
        // - The index of the signature to check. See
        return $jwsVerifier->verifyWithKey($jws, $jwk, 0);
    }
    /**
     *  Chekers
     *  AlgorithmChecker(array $supportedAlgorithms,    bool $protectedHeader = false)
     *      @var supportedAlgorithms - массив из доспутимых алгоритмов (хеширования?)
     *  AudienceChecker(string $audience,               bool $protectedHeader = false)
     *      @var audience - название приложения, которому выдан токен
     *  ExpirationTimeChecker(int $allowedTimeDrift = 0,bool $protectedHeaderOnly = false)
     *      @var allowedTimeDrift - допустимое кол-во дополнительных секунд для срока действия токена
     *  IssuedAtChecker(int $allowedTimeDrift = 0,      bool $protectedHeader = false)
     *      @var allowedTimeDrift - допустимое колво секунд от time() до значения когда выдан токен
     *  IssuerChecker(array $issuer,                    bool $protectedHeader = false)
     *      @var issuer - массив из доспутимых поставщиков
     *  NotBeforeChecker(int $allowedTimeDrift = 0,     bool $protectedHeaderOnly = false)
     *      @var allowedTimeDrift  - допустимое колво секунд от time() до значения когда можно использовать токен
     */

    private static function verify_payload($claims,$refresh_verifying = false)
    {
        $checkers = [
            new Checker\IssuedAtChecker(),
            new Checker\IssuerChecker([JWT::$jwt_provider]),
            new Checker\NotBeforeChecker(),
            new Checker\AudienceChecker('Odin Labs'),
        ];

        // If need to refresh token don't check it 'exp'
        if (!$refresh_verifying){
            $checkers[] = new Checker\ExpirationTimeChecker();
            // throw new Exception($refresh_verifying, 1);

        }

        $claimCheckerManager = new ClaimCheckerManager($checkers);
        $claimCheckerManager->check($claims, [
            'iat',  // token was generated at time
            'iss',  // token provider
            'nbf',  // token invalid before time
            'exp',  // token invalid after time
            'aud',  // token receiver application
        ]);
    }

    public static function data($token)
    {
        // The serializer manager. We only use the JWS Compact Serialization Mode.
        $serializerManager = new JWSSerializerManager([
            new CompactSerializer(),
        ]);

        // We try to load the token.
        $jws = $serializerManager->unserialize($token);

        return json_decode($jws->getPayload());
    }

    public static function find($refresh_token)
    {
        return JWT::where('token',hash('sha256', $refresh_token))->first();
    }

}

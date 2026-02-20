<?php

namespace Modules\AppChannelYoutubeChannels\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppChannelYoutubeChannelsController extends Controller
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $callbackUrl;
    protected $scopes;

    public function __construct()
    {
        \Access::check('appchannels.' . module('key'));

        $this->clientId = get_option("youtube_client_id", "");
        $this->clientSecret = get_option("youtube_client_secret", "");
        $this->callbackUrl = module_url();
        $this->scopes = get_option("youtube_scopes", "https://www.googleapis.com/auth/youtube,https://www.googleapis.com/auth/youtube.upload,https://www.googleapis.com/auth/youtube.readonly");

        if (!$this->clientId || !$this->clientSecret) {
            \Access::deny(__('To use YouTube, you must first configure the Client ID and Client Secret.'));
        }

        try {
            $this->client = new \Google_Client();
            $this->client->setClientId($this->clientId);
            $this->client->setClientSecret($this->clientSecret);
            $this->client->setRedirectUri($this->callbackUrl);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('consent');
            $this->client->setScopes(explode(',', $this->scopes));
        } catch (\Exception $e) {
            \Log::error('YouTube Google Client init error', ['error' => $e->getMessage()]);
            \Access::deny(__('Could not initialize Google API client: ') . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $result = [];
        try
        {
            if( !session("Youtube_AccessToken") )
            {
                if(!$request->code)
                {
                    return redirect( module_url("oauth") );
                }

                $token = $this->client->fetchAccessTokenWithAuthCode($request->code);

                if (isset($token['error'])) {
                    throw new \Exception($token['error_description'] ?? $token['error']);
                }

                session( ['Youtube_AccessToken' => $token] );
                return redirect( $this->callbackUrl );
            }
            else
            {
                $token = session("Youtube_AccessToken");
            }

            $this->client->setAccessToken($token);

            if ($this->client->isAccessTokenExpired() && !empty($token['refresh_token'])) {
                $this->client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                $token = $this->client->getAccessToken();
                if (empty($token['refresh_token']) && !empty(session("Youtube_AccessToken")['refresh_token'])) {
                    $token['refresh_token'] = session("Youtube_AccessToken")['refresh_token'];
                }
                session(['Youtube_AccessToken' => $token]);
            }

            $youtube = new \Google\Service\YouTube($this->client);
            $channels = $youtube->channels->listChannels('snippet,contentDetails,statistics', [
                'mine' => true
            ]);

            foreach ($channels->getItems() as $ch) {
                $snippet = $ch->getSnippet();
                $thumbnails = $snippet->getThumbnails();
                $avatar = $thumbnails ? ($thumbnails->getDefault() ? $thumbnails->getDefault()->getUrl() : '') : '';

                $result[] = [
                    'id' => $ch->getId(),
                    'name' => $snippet->getTitle(),
                    'avatar' => $avatar,
                    'desc' => __("Channel"),
                    'link' => "https://www.youtube.com/channel/" . $ch->getId(),
                    'oauth' => $token,
                    'module' => $request->module['module_name'],
                    'reconnect_url' => $request->module['uri']."/oauth",
                    'social_network' => 'youtube',
                    'category' => 'channel',
                    'login_type' => 1,
                    'can_post' => 1,
                    'data' => "",
                    'proxy' => 0,
                ];

                $channels_data = [
                    'status' => 1,
                    'message' => __('Succeeded')
                ];
            }

            if(empty($result))
            {
                $channels_data = [
                    'status' => 0,
                    'message' => __('No channels found'),
                ];
            }

        }
        catch (\Exception $e)
        {
            $channels_data = [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }

        $channels_data = array_merge($channels_data ?? ['status' => 0, 'message' => __('Unknown error')], [
            'channels' => $result,
            'module' => $request->module,
            'save_url' => url_app('channels/save'),
            'reconnect_url' => module_url('oauth'),
            'oauth' => session("Youtube_AccessToken")
        ]);

        session( ['channels' => $channels_data] );
        return redirect( url_app("channels/add") );
    }

    public function oauth(Request $request)
    {
        $request->session()->forget('Youtube_AccessToken');
        $authUrl = $this->client->createAuthUrl();
        return redirect($authUrl);
    }

    public function settings(){
        return view('appchannelyoutubechannels::settings');
    }
}

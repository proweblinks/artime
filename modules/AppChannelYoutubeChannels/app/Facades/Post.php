<?php
namespace Modules\AppChannelYoutubeChannels\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AppChannels\Models\Accounts;
use Media;

class Post extends Facade
{
    protected static $client;
    protected static $youtube;

    protected static function getFacadeAccessor()
    {
        return ex_str(__NAMESPACE__);
    }

    protected static function initYouTube($account)
    {
        self::$client = new \Google_Client();
        self::$client->setClientId(get_option("youtube_client_id", ""));
        self::$client->setClientSecret(get_option("youtube_client_secret", ""));
        self::$client->setAccessType('offline');

        if ($account && $account->token) {
            $token = json_decode($account->token, true);
            self::$client->setAccessToken($token);

            if (self::$client->isAccessTokenExpired()) {
                if (!empty($token['refresh_token'])) {
                    self::$client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                    $newToken = self::$client->getAccessToken();
                    if (empty($newToken['refresh_token'])) {
                        $newToken['refresh_token'] = $token['refresh_token'];
                    }
                    $account->token = json_encode($newToken);
                    $account->save();
                } else {
                    Accounts::where("id", $account->id)->update(["status" => 0]);
                    throw new \Exception(__("YouTube session expired. Please reconnect."));
                }
            }
        }

        self::$youtube = new \Google\Service\YouTube(self::$client);
    }

    protected static function validator($post)
    {
        $errors = [];
        $data = json_decode($post->data, false);
        $medias = $data->medias ?? [];

        if (empty($medias)) {
            $errors[] = __("YouTube: Please select a video.");
        } else {
            $media = Media::url($medias[0]);
            if (!Media::isVideo($media)) {
                $errors[] = __("YouTube only supports video uploads.");
            }
        }

        if (empty($data->caption)) {
            $errors[] = __("YouTube: Please enter a title for the video.");
        }

        return $errors;
    }

    protected static function post($post)
    {
        $account = $post->account;

        try {
            self::initYouTube($account);
        } catch (\Exception $e) {
            return [
                "status"  => "error",
                "message" => $e->getMessage(),
                "type"    => $post->type,
            ];
        }

        $data = json_decode($post->data, false);
        $medias = $data->medias ?? [];
        $caption = spintax($data->caption ?? '');
        $options = $data->options ?? (object)[];

        $errors = self::validator($post);
        if ($errors) {
            return [
                "status"  => "error",
                "message" => implode(', ', $errors),
                "type"    => $post->type,
            ];
        }

        $title       = mb_substr($caption, 0, 100);
        $description = $options->yt_description ?? $caption;
        $tags        = !empty($options->yt_tags) ? explode(',', $options->yt_tags) : [];
        $privacy     = $options->yt_privacy ?? 'public';
        $categoryId  = $options->yt_category ?? '22';

        $videoPath = Media::path($medias[0]);

        try {
            $uploadResult = self::uploadVideo($videoPath, $title, $description, $tags, $privacy, $categoryId);
            if ($uploadResult['status'] != 1) {
                return $uploadResult;
            }
            return [
                "status"  => 1,
                "message" => __("Successfully uploaded to YouTube"),
                "id"      => $uploadResult['id'],
                "url"     => $uploadResult['url'],
                "type"    => "media"
            ];
        } catch (\Exception $e) {
            return [
                "status"  => 0,
                "message" => __("YouTube error: ") . $e->getMessage(),
                "type"    => $post->type,
            ];
        }
    }

    protected static function uploadVideo($videoPath, $title, $description, $tags, $privacy, $categoryId)
    {
        try {
            $snippet = new \Google\Service\YouTube\VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setTags($tags);
            $snippet->setCategoryId($categoryId);

            $status = new \Google\Service\YouTube\VideoStatus();
            $status->setPrivacyStatus($privacy);
            $status->setSelfDeclaredMadeForKids(false);

            $video = new \Google\Service\YouTube\Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            self::$client->setDefer(true);
            $insertRequest = self::$youtube->videos->insert('snippet,status', $video);

            $chunkSizeBytes = 10 * 1024 * 1024;
            $media = new \Google\Http\MediaFileUpload(
                self::$client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($videoPath));

            $uploadStatus = false;
            $handle = fopen($videoPath, "rb");
            while (!$uploadStatus && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $uploadStatus = $media->nextChunk($chunk);
            }
            fclose($handle);
            self::$client->setDefer(false);

            if ($uploadStatus && isset($uploadStatus['id'])) {
                $videoId = $uploadStatus['id'];
                return [
                    "status" => 1,
                    "id"     => $videoId,
                    "url"    => "https://www.youtube.com/watch?v=" . $videoId,
                ];
            }

            return [
                "status"  => 0,
                "message" => __("YouTube upload failed."),
            ];
        } catch (\Exception $e) {
            return [
                "status"  => 0,
                "message" => $e->getMessage(),
            ];
        }
    }
}

<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

$options = getopt('', array('from:', 'to:', 'api:', 'profile::', 'mode::', 'full', 'no-header'));

$profile = (isset($options['profile']) ? $options['profile'] : 'car');
$mode = (isset($options['mode']) && in_array($options['mode'], array('closest', 'fastest')) ? $options['mode'] : 'fastest');
$header = !(isset($options['no-header']));
$full = (isset($options['full']));
$fileFrom = $options['from'];
$fileTo = $options['to'];
$api = $options['api'];

$cursorFrom = 0;
$cursorTo = 0;

echo '--------------------------------------------------'.PHP_EOL;
echo 'Profile : '.$profile.PHP_EOL;
echo 'Mode    : '.$mode.PHP_EOL;
echo '--------------------------------------------------'.PHP_EOL;

$client = new Client(['base_uri' => $api]);

if (isset($fileFrom) && file_exists($fileFrom)) {
  $fname = pathinfo($fileFrom, PATHINFO_FILENAME);
  $dir = 'data/'.$fname;

  if (!file_exists($dir) || !is_dir($dir)) {
    mkdir($dir);
  } else {
    $i = 1;
    while (file_exists($dir) && is_dir($dir)) {
      $dir = 'data/'.$fname.' ('.$i.')';
      $i++;
    }
    mkdir($dir);
  }

  if (($handleFrom = fopen($fileFrom, 'r')) !== FALSE && ($handleTo = fopen($fileTo, 'r')) !== FALSE) {
    $fpResult = fopen($dir.'/result.csv', 'w');
    if ($full === TRUE) {
      $fpResultFull = fopen($dir.'/result-full.csv', 'w');
    }

    if ($header === TRUE && ($headerFrom = fgetcsv($handleFrom, 1000)) !== FALSE && ($headerTo = fgetcsv($handleTo, 1000)) !== FALSE) {
      $headerFrom = array_map(function ($column) { return 'from_'.$column; }, $headerFrom);
      $headerTo = array_map(function ($column) { return 'to_'.$column; }, $headerTo);

      fputcsv($fpResult, array_merge($headerFrom, $headerTo, array('mode','distance','time')));
      if ($full === TRUE) {
        fputcsv($fpResultFull, array_merge($headerFrom, $headerTo, array('mode','distance','time')));
      }
      rewind($handleTo);
    }

    while (($dataFrom = fgetcsv($handleFrom, 1000)) !== FALSE) {
      $min = NULL;
      $minTo = NULL;
      $minResult = NULL;

      while (($dataTo = fgetcsv($handleTo, 1000)) !== FALSE) {
        if ($header === TRUE && $cursorTo === 0) {
          $cursorTo++;
          continue;
        }

        $response_error = FALSE;
        try {
          $url = sprintf('routing?profile=%s&loc=%f,%f&loc=%f,%f&sort=true', $profile, $dataFrom[1], $dataFrom[0], $dataTo[1], $dataTo[0]);
          //echo $cursorFrom.'   ||   '.$cursorTo.'   ||   '.$url.PHP_EOL;
          $response = $client->get($url, [
              'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'PHP/'.phpversion()
              ]
          ]);
          $json = json_decode((string)$response->getBody());

          $last = end($json->features);

          if ($full === TRUE) {
            $result = array_merge(
              $dataFrom,
              $dataTo,
              array($mode, $last->properties->distance, $last->properties->time)
            );
            fputcsv($fpResultFull, $result);
          }

          if ($mode === 'closest' && (is_null($min) || $last->properties->distance < $min)) {
            $min = $last->properties->distance;
            $minTo = $dataTo;
            $minResult = array($last->properties->distance, $last->properties->time);
          }
          else if ($mode === 'fastest' && (is_null($min) || $last->properties->time < $min)) {
            $min = $last->properties->time;
            $minTo = $dataTo;
            $minResult = array($last->properties->distance, $last->properties->time);
          }
        } catch (ClientException $e) {
          $request = $e->getRequest();
          $response_error = $e->getResponse();
          echo $cursorFrom.' | '.$dataFrom[2].' | ERROR: [client] '.$request->getUri().'  '.$response_error->getStatusCode().' '.$response_error->getReasonPhrase().PHP_EOL;
        } catch (ServerException $e) {
          $request = $e->getRequest();
          $response_error = $e->getResponse();
          echo $cursorFrom.' | '.$dataFrom[2].' | ERROR: [server] '.$request->getUri().'  '.$response_error->getStatusCode().' '.$response_error->getReasonPhrase().PHP_EOL;
        } catch (Exception $e) {
          $request = $e->getRequest();
          $response_error = $e->getResponse();
          echo $cursorFrom.' | '.$dataFrom[2].' | ERROR: '.$request->getUri().'  '.$response_error->getStatusCode().' '.$response_error->getReasonPhrase().PHP_EOL;
        }

        $cursorTo++;

        if ($response_error !== FALSE) {
          $fpError = fopen($dir.'/errors.csv', 'a');
          fputcsv($fpError, $dataFrom);
          fclose($fpError);

          break;
        }
      }

      if ($response_error === FALSE) {
        echo $cursorFrom.' | '.$dataFrom[2].' | '.$minTo[2].' | Distance: '.$minResult[0].' - Time: '.$minResult[1].PHP_EOL;

        $result = array_merge(
          $dataFrom,
          $minTo,
          array($mode),
          $minResult
        );
        fputcsv($fpResult, $result);
      }

      rewind($handleTo);
      $cursorTo = 0;

      $cursorFrom++;
    }

    fclose($fpResult);
    if ($full === TRUE) {
      fclose($fpResultFull);
    }
  }
}

exit();

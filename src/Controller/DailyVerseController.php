<?php
namespace Drupal\daily_verse\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use GuzzleHttp\Client;

/**
 * Returns responses for Daily Verse routes.
 */
class DailyVerseController extends ControllerBase {

  public function displayVerse() {
    $ip = \Drupal::request()->getClientIp();
    $connection = Database::getConnection();
    
    // Get verses already seen by this IP
    $query = $connection->select('verse_tracking', 'v')
      ->fields('v', ['verse_id'])
      ->condition('ip_address', $ip)
      ->execute();
    $seen_verses = $query->fetchCol();
    
    // Define Bible and chapter-verse data
    $bible_data = [
      'de4e12af7f28f599-02' => 'GEN',
      'de4e12af7f28f599-01' => 'PSA',
    ];
    $chapter_verse_data = [
      'GEN' => ['max_chapter' => 50, 'max_verse' => 31],
      'PSA' => ['max_chapter' => 150, 'max_verse' => 6],
    ];
    
    // Generate a new verse that hasn't been seen
    $attempts = 0;
    do {
      // Randomly pick a Bible book
      $bible_keys = array_keys($bible_data);
      $random_bible_key = $bible_keys[array_rand($bible_keys)];
      $book = $bible_data[$random_bible_key];
      
      // Get random chapter and verse
      $max_chapter = $chapter_verse_data[$book]['max_chapter'];
      $max_verse = $chapter_verse_data[$book]['max_verse'];
      $random_chapter = rand(1, $max_chapter);
      $random_verse = rand(1, $max_verse);
      
      // Generate verse ID
      $verse_id = "{$book}.{$random_chapter}.{$random_verse}";
      $reference = "{$book} {$random_chapter}:{$random_verse}";
      
      $attempts++;
      if ($attempts > 50) {
        // Fallback: Break the loop after 50 attempts to avoid infinite loop
        break;
      }
    } while (in_array($verse_id, $seen_verses));
    
    // Fetch verse details from the API
    $client = new Client();
    $api_key = 'd9b772d3b0f504a3835152c56ff23ecb';
    try {
      $response = $client->request('GET', "https://api.scripture.api.bible/v1/bibles/{$random_bible_key}/verses/{$verse_id}", [
        'headers' => [
          'api-key' => $api_key,
        ],
      ]);
      
      $verse_details = json_decode($response->getBody(), TRUE)['data'];
      $verse_text = preg_replace('/(\d+)([^\d])/', '$1 $2', $verse_details['content']);
      
      // Save the seen verse
      $connection->insert('verse_tracking')
        ->fields([
          'ip_address' => $ip,
          'verse_id' => $verse_id,
          'last_access' => date('Y-m-d H:i:s'),
        ])
        ->execute();
      
      // Randomize background color
      $colors = ['#f8d7da', '#d4edda', '#cce5ff', '#fff3cd', '#d1ecf1'];
      $random_color = $colors[array_rand($colors)];
      
      return [
        '#theme' => 'verse_display',
        '#verse_text' => $verse_text,
        '#verse_reference' => $reference,
        '#background_color' => $random_color,
      ];
    } catch (\Exception $e) {
      \Drupal::logger('daily_verse')->error($e->getMessage());
      return [
        '#markup' => '<p>There was an error fetching verses. Please try again later.</p>',
      ];
    }
  }
  
}

<?php
/**
 * Plugin Name: Month in Review
 * Plugin URI: https://github.com/c99koder/month-in-review
 * Description: Shortcode and custom page type that generates a summary of the previous week using data exported from InfluxDB
 * Version: 1.0.0
 * Author: Sam Steele
 * Author URI: https://www.c99.org/
 * License: Apache-2.0
 * License URI: https://www.apache.org/licenses/LICENSE-2.0.txt
 */

$C99MIR_POST_CATEGORY = 0;
$C99MIR_REPORT_DATA_PATH = "";

function c99mir_sum($data, $property) {
  $total = 0;
  foreach($data as $d) {
    $total += $d->$property;
  }
  return $total;
}

function c99mir_mean($data, $property) {
  $total = 0;
  foreach($data as $d) {
    $total += $d->$property;
  }
  return $total / count($data);
}

function c99mir_delta($v1, $v2, $units="", $invertColors=false) {
    $delta = $v1 - $v2;
    if($delta > 0)
      $span_class = $invertColors ? "negative" : "positive";
    else if($delta < 0)
      $span_class = $invertColors ? "positive" : "negative";
    else
      $span_class = "neutral";

    return "<span class='c99mir_delta_" . $span_class . "'>" . ($delta > 0 ? '+' : '') . number_format($delta) . $units . "</span> ";
}

function c99mir_productive_time($data) {
  $total = 0;
  foreach($data as $d) {
    if($d->productivity > 0)
      $total += $d->duration;
  }
  return $total;
}

function c99mir_distracting_time($data) {
  $total = 0;
  foreach($data as $d) {
    if($d->productivity < 0)
      $total += $d->duration;
  }
  return $total;
}

function c99mir_seconds_to_hours($seconds, $show_seconds=true) {
  return sprintf("%2dh %2dm", floor($seconds/3600), ($seconds/60)%60);
}

function c99mir_report($year, $month) {
  global $C99MIR_REPORT_DATA_PATH;
  $year = intval($year);
  $month = intval($month);
  $o = "";
  $current = json_decode(file_get_contents($C99MIR_REPORT_DATA_PATH . $year . "-" . $month . ".json"));
  $prev = json_decode(file_get_contents($C99MIR_REPORT_DATA_PATH . ($month == 1 ? $year - 1 : $year) . "-" . ($month == 1 ? 12 : $month - 1) . ".json"));

  if(count($current->fitbit->steps) > 0) {
    $o .= "<h1>Fitness</h1>";

    $avg = c99mir_mean($current->fitbit->restingHeartRate, "value");
    $o .= "<div class='c99mir_stat'><span class='fas fa-heartbeat c99mir_stat_icon'></span>Avg. Resting Heart Rate<br/><b>" . number_format($avg) . " bpm</b><br/>" . c99mir_delta($avg, c99mir_mean($prev->fitbit->restingHeartRate, "value"), " bpm", true) . "from last month</div>";

    $avg = c99mir_mean($current->fitbit->steps, "value");
    $o .= "<div class='c99mir_stat'><span class='fas fa-shoe-prints c99mir_stat_icon'></span>Avg. Steps Per Day<br/><b>" . number_format($avg) . "</b><br/>" . c99mir_delta($avg, c99mir_mean($prev->fitbit->steps, "value")) . "from last month</div>";

    $avg = c99mir_mean($current->fitbit->weight, "value");
    $o .= "<div class='c99mir_stat'><span class='fas fa-weight c99mir_stat_icon'></span>Avg. weight<br/><b>" . number_format($avg) . " lbs</b><br/>" . c99mir_delta($avg, c99mir_mean($prev->fitbit->weight, "value"), " lbs") . "from last month</div>";

    $avg = c99mir_mean($current->fitbit->sleep, "minutes_asleep");
    $o .= "<div class='c99mir_stat'><span class='fas fa-bed c99mir_stat_icon'></span>Avg. Time Asleep<br/><b>" . c99mir_seconds_to_hours($avg*60,false) . "</b><br/>" . c99mir_delta($avg, c99mir_mean($prev->fitbit->sleep, "minutes_asleep"), " min") . "from last month</div>";

    $sum = c99mir_sum($current->fitbit->distance, "value");
    $o .= "<div class='c99mir_stat'><span class='fas fa-walking c99mir_stat_icon'></span>Distance Traveled<br/><b>" . number_format($sum) . " miles</b><br/>" . c99mir_delta($sum, c99mir_sum($prev->fitbit->distance, "value"), " mi") . "from last month</div>";

    $sum = c99mir_sum($current->fitbit->floors, "value");
    $o .= "<div class='c99mir_stat'><span class='fas fa-mountain c99mir_stat_icon'></span>Floors Climbed<br/><b>" . number_format($sum) . "</b><br/>" . c99mir_delta($sum, c99mir_sum($prev->fitbit->floors, "value")) . "from last month</div>";
  }

  if(count($current->rescuetime->activity) > 0) {
    $o .= "<p style='clear: both'/>";
    $o .= "<h1>Productivity</h1>";

    $sum = c99mir_productive_time($current->rescuetime->activity);
    $o .= "<div class='c99mir_stat'><span class='fas fa-laptop-code c99mir_stat_icon'></span>Productive Time<br/><b>" . c99mir_seconds_to_hours($sum) . "</b><br/>" . c99mir_delta($sum/3600, c99mir_productive_time($prev->rescuetime->activity, "value")/3600, " hours") . "from last month</div>";

    $sum = c99mir_distracting_time($current->rescuetime->activity);
    $o .= "<div class='c99mir_stat'><span class='fas fa-gamepad c99mir_stat_icon'></span>Distracting Time<br/><b>" . c99mir_seconds_to_hours($sum) . "</b><br/>" . c99mir_delta($sum/3600, c99mir_distracting_time($prev->rescuetime->activity, "value")/3600, " hours", true) . "from last month</div>";
  }

  if(count($current->instagram->post) > 0) {
    $o .= "<p style='clear: both'/>";
    $o .= "<h1>Instagram</h1>";

    $sum = count($current->instagram->post);
    $o .= "<div class='c99mir_stat'><span class='fas fa-camera c99mir_stat_icon'></span>Photos Shared<br/><b>" . number_format($sum) . "</b><br/>" . c99mir_delta($sum, count($prev->instagram->post)) . "from last month</div>";

    $sum = c99mir_sum($current->instagram->post, "likes");
    $o .= "<div class='c99mir_stat'><span class='fas fa-heart c99mir_stat_icon'></span>Favorites<br/><b>" . number_format($sum) . "</b><br/>" . c99mir_delta($sum, c99mir_sum($prev->instagram->post, "likes")) . "from last month</div>";

    $sum = c99mir_sum($current->instagram->post, "comments");
    $o .= "<div class='c99mir_stat'><span class='fas fa-comments c99mir_stat_icon'></span>Comments<br/><b>" . number_format($sum) . "</b><br/>" . c99mir_delta($sum, c99mir_sum($prev->instagram->post, "comments")) . "from last month</div>";
  }

  if(count($current->gaming->time) > 0) {
    $o .= "<p style='clear: both'/>";
    $o .= "<h1>Recent Games</h1>";
    $found = [];
    foreach($current->gaming->time as $game) {
      if($found[$game->application_id] != 1) {
        $o .= "<div class='c99mir_recent_game'><a href='$game->url'><img src='$game->image'/></a></div>";
        $found[$game->application_id] = 1;
      }
    }
  }

  if(count($current->gaming->achievement) > 0) {
    $o .= "<p style='clear: both'/>";
    $o .= "<h1>Achievements</h1>";
    foreach($current->gaming->achievement as $achievement) {
        $o .= "<div class='c99mir_achievement'><img src='$achievement->icon'/>$achievement->title<br/><b>$achievement->name</b></div>";
    }
  }

  return $o;
}

function c99mir_shortcode( $atts ) {
  $atts = shortcode_atts(
    array(
      'year' => '',
      'month' => '',
    ),
    $atts,
    'c99mir'
  );

  return c99mir_report($atts['year'], $atts['month']);
}

function c99mir_create_journal( $year, $month ) {
  global $C99MIR_POST_CATEGORY;

  if(intval($month) < 10)
    $date = strval($year) . '-0' . strval($month);
  else
    $date = strval($year) . '-' . strval($month);
  if ($pages = get_posts(array(
    'post_type' => 'c99mir_post',
    'numberposts' => -1,
    'post_status' => array('publish', 'private', 'future')))) {
      foreach ($pages as $page) {
        if ($page->post_name == $date) {
          return;
        }
      }
  }

  wp_insert_post(
    array('post_type' => 'c99mir_post',
          'post_date' => date("Y-m-t", strtotime($date . '-01')) . ' 23:59:59',
          'post_title' => date("F", strtotime($date . '-01')) . ' ' . strval($year) . ' In Review',
          'post_name' => $date,
          'post_author' => '4',
          'post_status' => 'publish',
          'post_category' => array($C99MIR_POST_CATEGORY),
          'post_content' => '[c99mir month="' . strval($month) . '" year="' . strval($year) . '"]'
          )
  );
}

function c99mir_init() {
  wp_enqueue_style('c99mir_style', plugins_url( '/style.css', __FILE__ ));
  wp_enqueue_style('fontawesome-5', '/wp-content/fontawesome/css/all.min.css', false, '5.12.0');

  register_post_type('c99mir_post',
                     array(
                         'labels'      => array(
                             'name'          => __('Monthly Reviews'),
                             'singular_name' => __('Monthly Review'),
                         ),
                         'public'      => true,
                         'has_archive' => true,
                         'taxonomies'  => array( 'category' ),
                         'rewrite'     => array( 'slug' => 'month-in-review' ),
                         'capability_type' => 'page'
                     )
  );
  add_shortcode( 'c99mir', 'c99mir_shortcode' );

  c99mir_create_journal(date("Y"), date("n"));
}
add_action( 'init', 'c99mir_init' );
 
function c99mir_install() {
    c99mir_init();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'c99mir_install' );

function c99mir_deactivation() {
    unregister_post_type( 'c99mir' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'c99mir_deactivation' );

function c99mir_add_post_type($query) {
    if(empty($query->query['post_type']) or $query->query['post_type'] === 'post') {
        $query->set('post_type', array('post', 'c99mir_post'));
    }
}
add_action('pre_get_posts', 'c99mir_add_post_type');
?>
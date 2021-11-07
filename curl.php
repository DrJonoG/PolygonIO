<?php
/**
* @author = DrJonoG  # Jonathon Gibbs
* Copyright 2016-2020 - https://www.jonathongibbs.com / @DrJonoG
* Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the
* License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
* Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an
* "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and limitations under the License.
**/

function getCurl($url) {
  $userAgents = explode("\n", file_get_contents(dirname(__FILE__) . '/data/user_agents.txt'));
  $proxies = explode("\n", file_get_contents(dirname(__FILE__) . '/data/http_proxies.txt'));


  # Get webpage
  $ch = curl_init();
  #curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
  # Use a random proxy from a given list. Need to specify own list, or leave commented out
  #curl_setopt($ch, CURLOPT_PROXY, preg_replace('/\s+/', '', $proxies[rand(0, count($proxies) - 1)]));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  # Use a random user agent from speicifed list
  curl_setopt($ch, CURLOPT_USERAGENT,$user_agents[rand(0, count($user_agents) - 1)]);
  curl_setopt($ch, CURLOPT_URL, $url);
  # Get page
  $content = curl_exec($ch);
  # Error checking
  if (curl_errno($ch)) {
      $error_msg = curl_error($ch);
      curl_close($ch);
      print($error_msg);
      exit;
  }
  curl_close($ch);
  # Return
  return $content;
}
?>

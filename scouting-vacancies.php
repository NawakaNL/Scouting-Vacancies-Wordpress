<?php
/**
 * Plugin Name: Scouting Vacancies
 * Plugin URI: https://www.tristandeboer.nl
 * Description: Display scouting vacancies on Wordpress Website.
 * Version: 0.1.5
 * Text Domain: scouting-vacancies-wordpress
 * Author: Tristan de Boer
 * Author URI: https://www.tristandeboer.nl
 */


function vacancies_display($atts) {
    $response = get_transient('vacancies_data');
		
	// Delete transient if no correct data is available (i.e. API has maintenance)
	if ($response["body"]->channel == null && $response != false) {
		delete_transient('vacancies_data');
	}
	
    // Check if response is set in database
    if ( false === $response) {
        // This code runs when there is no valid transient set
        // Get a response
        if (isset($attr['url'])) {
            $response = wp_remote_get($attr['url']);
        } else {
            $response = wp_remote_get('https://sol.scouting.nl/hrm/vacancies/api.xml');
        }


        if (is_wp_error($response)) {
            return "Er was een fout bij het ophalen van de vacatures.";
        }

        set_transient('vacancies_data', $response, 60 * 60);
    }
	
    // Check if a GET parameter is set, for viewing a single vacancy
	$vacancy = $_GET['vacancy'];
	
    // Decode XML to SimpleXML format
	$xml = simplexml_load_string(utf8_decode($response['body']));
	
    // Check if viewing single vacancy
	if ($vacancy) {
		foreach($xml->channel->item as $item)
		{
			if ($item->link == $vacancy) {
				$html .= '<article>';
				$html .= "<h1>".$item->title."</h1>";
				$html .= "<h3>".$item->section->name."</h3>";
				$html .= "<p>".$item->description."</p>";
				$html .= "<h6>Vanaf</h6>";
				$html .= "<p>".$item->period->from."</p>";
				$html .= "<h6>Tot</h6>";

				$html .= "<p>".$item->period->to."</p>";
				$html .= '<div class="elementor-button-wrapper">
								<a href="'.$item->link.'" class="elementor-button-link elementor-button elementor-size-sm" role="button">
											<span class="elementor-button-content-wrapper">
											<span class="elementor-button-icon elementor-align-icon-right">
									<i aria-hidden="true" class="fas fa-arrow-right"></i>			</span>
											<span class="elementor-button-text">Reageer als lid van scouting</span>
							</span>
										</a>
								<a href="/contact" class="elementor-button-link elementor-button elementor-size-sm" role="button">
											<span class="elementor-button-content-wrapper">
											<span class="elementor-button-icon elementor-align-icon-right">
									<i aria-hidden="true" class="fas fa-arrow-right"></i>			</span>
											<span class="elementor-button-text">Reageer als niet-lid van Scouting</span>
							</span>
										</a>
							</div>';
			}
		}
	} else {
		$html = "";
		if (isset($atts['title'])) {
			$html .= "<h1>".$atts['title']."</h1>";
		}
		// Group vacancies by section
		$vacancies = array();
		foreach($xml->channel->item as $item) {
			if (isset($item->section->name)) {
				$vacancies[(string)$item->section->name][] = $item;
			}
		}
		
		// Loop grouped sections
		foreach($vacancies as $section)
		{
			$section_title = "";
			foreach ($section as $item) {
				if (!isset($atts["organisation"]) || $item->organisation->attributes()->id == $atts["organisation"]) {
					// Append new section, create a title
					if (strcmp($item->section->name, $section_title)) {
						$html .= "<h2>".$item->section->name."</h2>";
						$section_title = $item->section->name;
						$html .= "<ul>";
					}

					// Add vacancy
					$html .= '<a href="?vacancy='.$item->link.'">';
					$html .= "<li>".$item->title."</li>";
					$html .= '</a>'; 
				}
			}
			if (!(isset($section_title) === true && $section_title === '')) {
				$html .= "</ul>";
			}
		}
	}
	
    return $html;
}

add_shortcode('vacancies', 'vacancies_display');
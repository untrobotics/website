<?php
/**
 * @param string $name First and last name of individual
 * @param string $title Their position or title in the org
 * @param string $description The description paragraph about the individual
 * @param string $picture_uri The URI of the individual's headshot
 * @param string|null $email The position's contact email. E.g., president@untrobotics.com
 * @param string|null $linkedin_url
 * @param string|null $github_url
 * @param string|null $twitter_url
 * @return void
 */
function get_member_card(string $name, string $title, string $description, string $picture_uri, ?string $email = null, ?string $linkedin_url = null, ?string $github_url = null, ?string $twitter_url = null){

    $contact_links_html = '';
    if($email !== null){
        $contact_links_html .= '<li>' .
                                    "<a href='mailto:{$email}' class='icon icon-sm text-primary fa-envelope'></a>" .
                                '</li>';
    }
    if($linkedin_url !== null){
        $contact_links_html .= '<li>' .
            "<a href='{$linkedin_url}' class='icon icon-sm text-primary fa-linkedin'></a>" .
        '</li>';
    }
    if($github_url !== null){
        $contact_links_html .=  '<li>' .
                                        "<a href='{$github_url}' class='icon icon-sm text-primary fa-github'></a>" .
                                '</li>';
    }
    if($twitter_url !== null){
        $contact_links_html .=  '<li>' .
            "<a href='{$twitter_url}' class='icon icon-sm text-primary fa-twitter'></a>" .
            '</li>';
    }

    echo '<div class="cell-lg-12 bio-area">' .
            '<div class="range range-sm-middle">' .
                '<div class="cell-md-3">' .
                    "<img src='{$picture_uri}' alt='' width='360' height='404' class='img-responsive' />" .
                '</div>' .
                '<div class="cell-md-6">' .
                    '<div class="inset-xl-right-70 inset-xl-left-70 inset-left-15 inset-right-15">' .
                        "<h6 class='h6-with-small'><p>{$name}</p><span class='small text-silver-chalice'>{$title}</span></h6>" .
                        "<p>{$description}</p>" .
                        '<ul class="list-inline-lg">' .
                            $contact_links_html .
                        '</ul>' .
                    '</div>' .
                '</div>' .
            '</div>' .
        '</div>';
}
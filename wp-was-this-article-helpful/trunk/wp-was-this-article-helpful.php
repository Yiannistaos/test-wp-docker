<?php
/**
 * Plugin Name:       Was This Article Helpful?
 * Plugin URI:        https://github.com/Yiannistaos/senior-wp-assestment-yiannis-christodoulou
 * Description:       Enable single-click voting on posts with immediate percentage feedback and secure one-time voting.
 * Version:           1.0.0
 * Author:            Yiannis Christodoulou
 * Author URI:        https://www.yiannistaos.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-was-this-article-helpful
 */

// Prevent direct access to the file for security reasons.
if (!defined('WPINC')) {
    die;
}

/**
 * Main class for the Was This Article Helpful plugin.
 */
class WasThisArticleHelpful {

    /**
     * Constructor method that sets up the plugin's functionality.
     */
    public function __construct() {
        // Enqueue the plugin's scripts and styles.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Append voting buttons to the content of each article.
        add_filter('the_content', array($this, 'append_vote_buttons'));

        // Register AJAX actions for logged-out and logged-in users.
        add_action('wp_ajax_nopriv_submit_vote', array($this, 'submit_vote'));
        add_action('wp_ajax_submit_vote', array($this, 'submit_vote'));

        // Add the meta box to the backend to display the voting results for each post.
        add_action('add_meta_boxes', array($this, 'add_voting_results_meta_box'));
    }

    /**
     * Enqueues the JavaScript and CSS files for the front end.
     */
    public function enqueue_scripts() {
        wp_enqueue_style('wp-was-this-article-helpful-css', plugins_url('/css/style.css', __FILE__), [], date('YmdHi'));
        wp_enqueue_script('wp-was-this-article-helpful-js', plugins_url('/js/script.js', __FILE__), array('jquery'), date('YmdHi'), true);
        wp_localize_script('wp-was-this-article-helpful-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('vote_nonce')));
    }

    /**
     * Filter function to append the voting buttons to the content.
     */
    public function append_vote_buttons($content) {
        if (is_single() && in_the_loop() && is_main_query()) {
            // Check if the user has already voted.
            $has_voted = get_post_meta(get_the_ID(), 'has_voted_' . $this->get_user_fingerprint(), true);
            
            if($has_voted) {
                // User has already voted, show results instead of buttons
                $content .= $this->display_results(get_the_ID());
            } else {
                // User has not voted, show voting buttons
                $content .= $this->render_buttons();
            }
        }
        return $content;
    }

    /**
     * Private method to generate the HTML for the voting results.
     */
    private function display_results($post_id) {
        $yes_votes = (int) get_post_meta($post_id, 'yes_votes', true);
        $no_votes = (int) get_post_meta($post_id, 'no_votes', true);
        $total_votes = $yes_votes + $no_votes;
        $yes_percentage = $total_votes > 0 ? ($yes_votes / $total_votes) * 100 : 0;
        $no_percentage = 100 - $yes_percentage; // Calculate the percentage of "No" votes
        $yes_percentage_txt = round($yes_percentage, 2) . '%';
        $no_percentage_txt = round($no_percentage, 2) . '%';

        // Check which option was selected by the user
        $user_vote = get_post_meta($post_id, 'has_voted_' . $this->get_user_fingerprint(), true);
        $yes_selected = $user_vote === 'yes' ? 'selected' : '';
        $no_selected = $user_vote === 'no' ? 'selected' : '';

        $html = <<<HTML
        <div class="was-this-helpful-container">
            <div class="thank-you-feedback-label">Thank you for your feedback.</div>
            <div class="vote-results">
                <div class="vote-result {$yes_selected}">
                    <span class="emoji-face happy-face"></span> {$yes_percentage_txt}
                </div>
                <div class="vote-result {$no_selected}">
                    <span class="emoji-face unhappy-face"></span> {$no_percentage_txt}
                </div>
            </div>
        </div>
    HTML;

        return $html;
    }

    /**
     * Private method to generate the HTML for the voting buttons.
     */
    private function render_buttons() {
        global $post; // Get the current post object

        $nonce = wp_create_nonce('article_vote_nonce');
        $post_id = $post->ID; // Get the current post ID
        $yes_txt = __('Yes', 'wp-was-this-article-helpful');
        $no_txt = __('No', 'wp-was-this-article-helpful');
        $was_this_helpful_txt = __('Was this article helpful?', 'wp-was-this-article-helpful');

        $html = <<<HTML
        <div class="was-this-helpful-container" id="thank-you-for-your-feedback">
            <div class="was-this-helpful-label">{$was_this_helpful_txt}</div>
            <div class="vote-buttons">
                <button class="vote-btn" data-vote="yes" data-nonce="{$nonce}" data-post_id="{$post_id}">
                    <span class="emoji-face happy-face"></span> {$yes_txt}
                </button>
                <button class="vote-btn" data-vote="no" data-nonce="{$nonce}" data-post_id="{$post_id}">
                    <span class="emoji-face unhappy-face"></span> {$no_txt}
                </button>
            </div>
        </div>
    HTML;
        return $html;
    }

    /**
     * AJAX handler for submitting a vote.
     */
    public function submit_vote() {
        // Verify the nonce for security
        $nonce = $_POST['nonce'];
        if (!wp_verify_nonce($nonce, 'article_vote_nonce')) {
            die('Security check!');
        }

        $post_id = intval($_POST['post_id']);
        $vote = sanitize_text_field($_POST['vote']);

        // Check if user has already voted (replace this with your own logic for IP checking)
        $has_voted = get_post_meta($post_id, 'has_voted_' . $this->get_user_fingerprint(), true);
        if ($has_voted) {
            $this->send_vote_results($post_id);
            exit;
        }

        // Save the vote
        $this->save_vote($post_id, $vote);

        // Send the updated results
        $this->send_vote_results($post_id);
    }

    /**
     * Save the user's vote to the database.
     */
    private function save_vote($post_id, $vote) {
        $current_yes = (int) get_post_meta($post_id, 'yes_votes', true);
        $current_no = (int) get_post_meta($post_id, 'no_votes', true);

        if ($vote === 'yes') {
            update_post_meta($post_id, 'yes_votes', $current_yes + 1);
        } else if ($vote === 'no') {
            update_post_meta($post_id, 'no_votes', $current_no + 1);
        }

        // Mark that the user has voted
        update_post_meta($post_id, 'has_voted_' . $this->get_user_fingerprint(), $vote);
    }

    /**
     * Send the voting results back to the client.
     */
    private function send_vote_results($post_id) {
        // Fetch the updated voting results from the database
        // Calculate the percentage of yes vs no votes
        $yes_votes = (int) get_post_meta($post_id, 'yes_votes', true);
        $no_votes = (int) get_post_meta($post_id, 'no_votes', true);
        $total_votes = $yes_votes + $no_votes;
        $yes_percentage = $total_votes > 0 ? ($yes_votes / $total_votes) * 100 : 0;
        $no_percentage = 100 - $yes_percentage; // Calculate the percentage of "No" votes
        $yes_percentage_txt = round($yes_percentage, 2) . '%';
        $no_percentage_txt = round($no_percentage, 2) . '%';

        // Check which option was selected by the user
        $user_vote = get_post_meta($post_id, 'has_voted_' . $this->get_user_fingerprint(), true);
        $yes_selected = $user_vote === 'yes' ? 'selected' : '';
        $no_selected = $user_vote === 'no' ? 'selected' : '';

        // Return the results
        wp_send_json_success(array(
            'yes_selected' => $yes_selected,
            'no_selected' => $no_selected,
            'yes_percentage_txt' => $yes_percentage_txt,
            'no_percentage_txt' => $no_percentage_txt,
            'yes_percentage' => $yes_percentage,
            'total_votes' => $total_votes
        ));
    }

    /**
     * Generate a unique fingerprint for the user to prevent double voting.
     */
    private function get_user_fingerprint() {
        // Use the user's IP address or other identifying information
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Add the meta box to the backend to display the voting results for each post.
     */
    public function add_voting_results_meta_box() {
        add_meta_box(
            'voting_results_meta_box',
            __('Voting Results', 'wp-was-this-article-helpful'), 
            array($this, 'voting_results_meta_box_callback'), 
            'post', 
            'side', 
            'high'
        );
    }

    /**
     * Callback function to display the voting results in the meta box.
     */
    public function voting_results_meta_box_callback($post) {
        // Fetch the voting results
        $yes_votes = (int) get_post_meta($post->ID, 'yes_votes', true);
        $no_votes = (int) get_post_meta($post->ID, 'no_votes', true);
        $total_votes = $yes_votes + $no_votes;
        $yes_percentage = $total_votes > 0 ? round(($yes_votes / $total_votes) * 100, 2) : 0;

        // Output the results
        echo '<p><strong><i>' . __('How many people found this article helpful?', 'wp-was-this-article-helpful') . '</i></strong></p>';
        echo '<p><strong>' . __('Yes:', 'wp-was-this-article-helpful') . '</strong> ' . $yes_votes;
        echo '&nbsp;&nbsp;|&nbsp;&nbsp;<strong>' . __('No:', 'wp-was-this-article-helpful') . '</strong> ' . $no_votes . '</p>';
        echo '<p><strong>' . __('Yes Percentage:', 'wp-was-this-article-helpful') . '</strong> ' . $yes_percentage . '%';
        echo '<br><strong>' . __('Total Votes:', 'wp-was-this-article-helpful') . '</strong> ' . $total_votes . '</br>';
    }
}

// Initialize the plugin.
$was_this_article_helpful = new WasThisArticleHelpful();
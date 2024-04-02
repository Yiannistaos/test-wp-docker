# Was This Article Helpful?

The **"Was This Article Helpful?"** WordPress plugin by [Yiannis Christodoulou](https://yiannistaos.com) is a sleek, user-centric tool designed to gather valuable feedback on your WordPress site's content. By enabling a simple and intuitive voting system, site visitors can quickly express their opinions on the articles they read, providing you with actionable insights to enhance your content strategy.

## Features

- **Simple Voting Interface**: Visitors can vote 'Yes' or 'No' at the end of each article, offering their feedback in just one click.
- **Real-time Feedback**: The plugin uses Ajax to submit votes seamlessly without reloading the page, ensuring a smooth user experience.
- **Persistent Vote Tracking**: Leveraging visitor fingerprinting technology, such as IP address checks, the system ensures a fair voting process by preventing multiple votes on the same article.
- **Instant Results Visualization**: After voting, users see the updated results displayed as a percentage, reflecting the collective feedback of all voters.
- **Admin Oversight**: Editors and admins can monitor voting statistics directly from the WordPress dashboard, with a meta widget displaying the results for each article.
- **Responsive Design**: The plugin is crafted to adapt to various screen sizes, ensuring that the voting feature is accessible on desktop, tablet, and mobile devices.



## Screenshot

![https://i.ibb.co/wBSV9s6/Screenshot-2024-01-06-at-23-49-04.png](https://i.ibb.co/wBSV9s6/Screenshot-2024-01-06-at-23-49-04.png)

## Installation

1. Download the plugin from [here](https://github.com/Yiannistaos/senior-wp-assestment-yiannis-christodoulou/archive/refs/heads/main.zip).
2. Navigate to your WordPress dashboard.
3. Go to Plugins > Add New > Upload Plugin.
4. Choose the downloaded plugin file and click 'Install Now'.
5. Once installed, activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

No configuration is needed for this plugin. Once activated, it automatically adds voting buttons to the end of each post.

## Usage

To use the plugin, follow these simple steps:

1. Navigate to any post on your WordPress site.
2. Scroll to the bottom of the post to find the "Was This Article Helpful?" section.
3. Click "Yes" or "No" to submit your feedback.
4. You will immediately see the updated vote percentages.

## For Administrators

As an admin, you can view the voting results directly in the WordPress backend:

1. Navigate to the 'Posts' section in your WordPress admin panel.
2. Click on any post to edit it.
3. On the post edit screen, look for the 'Voting Results' meta box on the side.
4. Here you will see the number of "Yes" and "No" votes, along with the percentage of positive votes.

## Developer Documentation

Inline code comments have been provided throughout the plugin files to explain the functionality and logic. Here's a brief overview:

-   `enqueue_scripts()`: Adds the necessary JavaScript and CSS files to your site's front end.
-   `append_vote_buttons()`: Inserts the voting buttons at the end of the post content.
-   `submit_vote()`: Handles the AJAX request when a vote is submitted, updates the vote count, and sends back the updated results.
-   `render_buttons()`: Generates the HTML markup for the voting buttons.
-   `display_results()`: Displays the voting results after a user has voted.
-   `add_voting_results_meta_box()`: Adds a meta box to the post edit screen in the admin to show voting results.

## Test Cases

This section includes test cases to verify the functionality of the "Was This Article Helpful?" plugin.

-   **Voting Functionality:** Check if the voting buttons appear on the post.
    Vote Submission: Test voting "Yes" and "No" and verify that the vote is recorded correctly.
-   **Vote Results:** After voting, the results should be displayed inside the voting buttons, and they should persist after a page refresh.
-   **Double Voting Prevention:** Ensure the system prevents the same user from voting more than once.
-   **Admin Results Display:** In the admin post edit screen, ensure that the meta box displays the correct voting results.
-   **Responsive Design:** Check that the voting buttons and results display correctly on different devices and screen sizes.

## License

This plugin is licensed under the GPLv2 (or later).

## Contributions

Contributions to the plugin are always welcome. You can contribute by reporting issues, suggesting improvements, or submitting pull requests.

For any questions or feedback, please contact the plugin author at [https://www.yiannistaos.com/](https://www.yiannistaos.com/).

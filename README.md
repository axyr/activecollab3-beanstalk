========================================
Beanstalk Integration (v2) for ActiveCollab 2
========================================

This extension for ActiveCollab 2 will allow you to use post-commit hooks in Beanstalk and make comments (and updates) to your tickets.

## Setup

#### Requirements

PHP 5.2 or at least the json_decode() function must be available. You can't install without it.


#### Installation

Upload both folders named "beanstalk" to the respective position in your ActiveCollab installation. Open the admin panel and install the module as usual.


#### Configuration

1. Open your Beanstalk repository and go to setup => integrations => Web hooks
2. Enter the URL to your AC installation like the example below

		https://projects.example.com/api.php?path_info=/projects/${id}/beanstalk/commit&token=${api}

3. Replace ${id} with the desired project ID, and ${api} with your API token (can be found in your user profile).


## How It Works

If you include a ticket ID in your commit message, a comment will be added to the ticket including a link to the changeset on Beanstalk. You can also report time to a ticket or the project.


1. Message Examples #1

	- Your Commit Message (Ticket #25)

			will add your message as comment to ticket #25

	- Your Commit Message (Completed Ticket #25)

			will add your message as comment and complete the ticket

2. Message Examples #2

	- Your Commit Message [#25]

			will add a comment to ticket #25

	- Your Commit Message [#25 time:2.00]

			will add a comment and report 2 hours on ticket #25

	- Your Commit Message [time:2.00]

			will report 2hrs on the linked project


--
### ToDos / Ideas

- Make a setting to enable/disable Beanstalk integration per project, and show the webhook URL.
- Allow to report time AND complete a ticket
- Support more options like setting status or milestone in commit message
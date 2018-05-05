<?php require_once __DIR__ . '/../bootstrap.php';

session_start();

use Squid\Patreon\Patreon;

// If the user does not have an access token in their session then we redirect
// them to the login page which will add an access token to their session and
// then redirect them back to this page.
if (! isset($_SESSION['accessToken'])) {
    Header('Location: login.php');
    exit;
}

// Create a new Patreon client using the Creator's Access Token, this allows
// us access to the campaign's resources.
$campaign = new Patreon(getenv('PATREON_ACCESS_TOKEN'));

// Create a new Patreon client using the access token we've obtained from the
// user who just logged in. This allows us access to the user's information,
// including their pledge to the creator's campaign.
$patron = new Patreon($_SESSION['accessToken']);

// Get the logged in User, this returns a Squid\Patreon\Entities\User
$me = $patron->me()->get();

// Get the creator's Campaign, this returns a Squid\Patreon\Entities\Campaign
$campaign = $campaign->campaigns()->getMyCampaign();

?>
<html>
  <head>
    <title><?php echo "{$campaign->creator->full_name} is creating {$campaign->creation_name}"; ?></title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body class="measure pad">
    <h1>Hello, <?php echo $me->full_name; ?>!</h1>
    <!-- If the user has an active pledge -->
    <?php if ($me->hasActivePledge()) { ?>
      <p>
        You are a patron of my campaign, thank you, that means everybody can
        see your picture when they visit this website! You have spent a total of
        $<?php echo $me->pledge->getTotalSpent(); ?> as my patron.
      </p>
      <!-- If the user has a reward -->
      <?php if ($me->pledge->hasReward()) { ?>
        <p>
          You have chosen to receive the <?php echo $me->pledge->reward->title; ?>
          reward, which has been chosen by <?php echo $me->pledge->reward->patron_count; ?>
          patrons.
        </p>
      <!-- Else, the user does not have a reward -->
      <?php } else { ?>
        <p>
          You have chosen not to receive a reward in return for your pledge.
          You could choose one of the following rewards:
        </p>
        <ul>
        <!-- loop through the campaign's rewards, listing them -->
          <?php foreach ($campaign->getAvailableRewards() as $reward) { ?>
            <li>
              <a href="<?php echo $reward->getPledgeUrl(); ?>">
                <?php echo $reward->title; ?>
              </a>
            </li>
          <?php } ?>
        </ul>

      <?php } ?>

    <!-- else, the user does not have an active pledge -->
    <?php } else { ?>
    <p>
      You aren't an active patron of my campaign. Please
      <a href="<?php echo $campaign->getPledgeUrl(); ?>">join</a>!
    </p>
    <?php } ?>
    <a href="/">&larr; Go back to the homepage</a>
  </body>
</html>

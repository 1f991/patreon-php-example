<?php require_once __DIR__ . '/../bootstrap.php';

use Squid\Patreon\Patreon;

// When the page is loaded for the first time we need to fetch the Campaign and
// Pledges from Patreon and save them into our local database so that they don't
// have to be downloaded every time someone visits the website.
if (! file_exists(DATABASE) || isset($_GET['refresh'])) {

    // Create a new Patreon client using the PATREON_ACCESS_TOKEN defined in the
    // .env file
    $patreon = new Patreon(getenv('PATREON_ACCESS_TOKEN'));

    // Populate the `$campaign` variable with the Campaign and include the
    // Campaign's Pledges
    $campaign = $patreon->campaigns()->getMyCampaignWithPledges();

    // Loop through each of the pledges and convert it into an array containing
    // just the values we need to display below.
    $patrons = $campaign->pledges->mapWithKeys(function ($pledge) {
        return [$pledge->patron->id => [
            'name' => $pledge->patron->full_name,
            'picture' => $pledge->patron->image_url,
            'per_payment' => number_format($pledge->amount_cents / 100, 2),
            'total_amount' => number_format($pledge->total_historical_amount_cents / 100, 2),
            'is_active' => $pledge->isActive(),
            'reward' => $pledge->hasReward() ? $pledge->reward->title : null,
        ]];
    });

    $data = [
        'pledge_url' => $campaign->pledge_url,
        'title' => "{$campaign->creator->full_name} is creating {$campaign->creation_name}",
        'patrons' => $patrons->toArray()
    ];

    file_put_contents(DATABASE, json_encode($data, JSON_PRETTY_PRINT));
}

$campaign = json_decode(file_get_contents(DATABASE));
?>
<html>
  <head>
    <title><?php echo $campaign->title; ?></title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <header class="bg-coral white pad">
      <h1 class="nm">Patreon PHP Example</h1>
      <p><?php echo $campaign->title; ?>.</p>
      <p>
        <a href="https://www.patreon.com<?php echo $campaign->pledge_url; ?>" class="fade">
          <img style="height: 40px;" src="https://c5.patreon.com/external/logo/become_a_patron_button@2x.png" alt="Become a Patron!" title="Become a Patron!"/>
        </a>
      </p>
      <p>
        or
        <a href="login.php">
          log in if you're already a patron &rarr;
        </a>
      </p>
    </header>
    <div class="banner bg-yellow">
      <p class="measure">
        You're viewing an example of how Patreon PHP can be used to create a
        website listing all patrons of a campaign (with instant updates). You
        can pledge to <a href="https://patreon.com/patreonphp">this campaign</a>
        to see yourself added here (don't worry, you'll never be charged).
      </p>
      <p class="measure">
        <a href="https://patreondevelopers.com/">Read more on Patreon Developers</a> /
        <a href="https://github.com/1f991/patreon-php">Patreon PHP</a> /
        <a href="https://github.com/1f991/patreon-php-example">Source Code</a>
      </p>
    </div>
    <div class="pad">
      <?php foreach ($campaign->patrons as $patron): ?>
        <div class="patron">
          <img src="<?php echo $patron->picture; ?>"/>
          <div class="about">
            <?php if ($patron->reward == "Emoji") { echo '&#x1f991;'; } ?>
            <span class="name <?php if ($patron->reward == "Purple Name") { echo 'purple'; } ?>">
              <?php echo $patron->name; ?>
            </span> — $<?php echo $patron->per_payment; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </body>
</html>

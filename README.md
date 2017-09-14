# Laravel Text Local API
### Introduction
This package allows you to use the TextLocal API in your Laravel 5.5+ app. The core class is a modified version of the demo class provided by TextLocal on: http://api.txtlocal.com/docs/phpclass and uses Guzzle to connect to the API and also provides some additional features not available in the demo class.

### Installation
**Step 1.** Install the package in one of two ways:

**EITHER** via composer:
```shell
composer require scottybo/textlocal
```
**OR** by adding the following to your composer.json file and running "composer update"
```php
"require": {
    ...
    "scottybo/textlocal": "1.3.*"
}
```
**Step 2.** Add the "TextLocal" facade in your config/app.php file
```php
'aliases' => [
    ...
    'TextLocal' => Illuminate\Support\Facades\TextLocal::class,
];
```
**Step 3.** You'll now need to publish the configuration file using the command below. A file will be created: config/textlocal.php
```shell
php artisan vendor:publish --provider="App\TextLocalApi\TextLocalServiceProvider" --tag="config"
```
**Step 4.** Add your TextLocal credentials to your .env file

**Important** Either specify a Key OR a Hash - don't enter both!
```
TEXTLOCAL_KEY=
TEXTLOCAL_USERNAME=
TEXTLOCAL_HASH=
```

### Example usage
**Important:** View the API docs to see which commands you can use: 

In this example we are going to create a command the grab received messages and displays them in the console, using the command
**php artisan textlocal:get-received-messages**
```php
<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use TextLocal;

class GetReceivedMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'textlocal:get-received-messages';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and store and messages received into Text Local';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Load our inboxes
        $inboxes = TextLocal::getInboxes();
        
        $start = 0;
        $limit = 1000;
        $min_time = strtotime('-1 day');
        $max_time = time(); // now
        
        // Loop through the inboxes
        foreach($inboxes->inboxes as $inbox) {
            
            // Load the messages for the current inbox (which we will call a folder)
            $folder = TextLocal::getMessages($inbox->id, $start, $limit, $min_time,$max_time);
            
            // If there are messages in the folder...
            if(sizeof($folder->messages) > 0) {
                foreach($folder->messages as $message) {
                    dump($message->message);
                }
            }
        }
    }
}
```

### Available commands

 * getLastRequest ()
* sendSms ($numbers, $message, $sender, $sched=null, $test=false, $receiptURL=null, $custom=null, $optouts=false, $simpleReplyService=false)
* sendSmsGroup ($groupId, $message, $sender=null, $sched=null, $test=false, $receiptURL=null, $custom=null, $optouts=false, $simpleReplyService=false)
* sendMms ($numbers, $fileSource, $message, $sched=null, $test=false, $optouts=false)
* sendMmsGroup ($groupId, $fileSource, $message, $sched=null, $test=false, $optouts=false)
* getUsers ()
* transferCredits ($user, $credits)
* getTemplates ()
* checkKeyword ($keyword)
* createGroup ($name)
* getContacts ($groupId, $limit, $startPos=0)
* createContacts ($numbers, $groupid= '5')
* createContactsBulk ($contacts, $groupid= '5')
* getGroups ()
* getMessageStatus ($messageid)
* getBatchStatus ($batchid)
* getSenderNames ()
* getInboxes ()
* getBalance ()
* getMessages ($inbox, $start, $limit, $min_time, $max_time)
* cancelScheduledMessage ($id)
* getScheduledMessages ()
* deleteContact ($number, $groupid=5)
* deleteGroup ($groupid)
* getSingleMessageHistory ($start, $limit, $min_time, $max_time)
* getAPIMessageHistory ($start, $limit, $min_time, $max_time)
* getEmailToSMSHistory ($start, $limit, $min_time, $max_time)
* getGroupMessageHistory ($start, $limit, $min_time, $max_time)
* getSurveys ()
* getSurveyDetails ()
* getSurveyResults ($surveyid, $start, $end)
* getOptouts ($time=null)


### Development

Want to contribute? Great - push away!

### Todos

 - Tests not properly implemented
 
### Licensed under MIT

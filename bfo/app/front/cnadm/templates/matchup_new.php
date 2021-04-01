
<?php $this->layout('base/layout', ['title' => 'Admin - Events']) ?>

<div class="mt-8">
        <h4 class="text-gray-600">Forms</h4>

        <div class="mt-4">
            <div class="p-6 bg-white rounded-md shadow-md">
                <h2 class="text-lg text-gray-700 font-semibold capitalize">New matchup</h2>
                
                <form>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
                        <div>
                            <label class="text-gray-700" for="username">Username</label>
                            <input class="form-input w-full mt-2 rounded-md focus:border-indigo-600" type="text">
                        </div>

                        <div>
                            <label class="text-gray-700" for="emailAddress">Email Address</label>
                            <input class="form-input w-full mt-2 rounded-md focus:border-indigo-600" type="email">
                        </div>

                        <div>
                            <label class="text-gray-700" for="password">Password</label>
                            <input class="form-input w-full mt-2 rounded-md focus:border-indigo-600" type="password">
                        </div>

                        <div>
                            <label class="text-gray-700" for="passwordConfirmation">Password Confirmation</label>
                            <input class="form-input w-full mt-2 rounded-md focus:border-indigo-600" type="password">
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <button class="px-4 py-2 bg-gray-800 text-gray-200 rounded-md hover:bg-gray-700 focus:outline-none focus:bg-gray-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


Add new fight:
<form method="post">
  <input type="text" id="team1" value="<?=$inteam1?>"> vs 
  <input type="text" id="team2" value="<?=$inteam2?>"><br><br>
  <select id="event-id">

    <?php foreach ($events as $event): ?>
        <option value="<?=$event->getID()?>" <?=$ineventid == $event->getID() ? ' selected' : ''?>><?=$event->getName()?> - <?=$event->getDate()?></option>
    <?php endforeach ?>

  </select>&nbsp;&nbsp;<input type="submit" id="create-matchup-button" value="Add fight">
</form>
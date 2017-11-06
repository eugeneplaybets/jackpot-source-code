<?php

class LotteryService implements LotteryContract
{
    /**
     * @param array $date
     *
     *
     * @since version
     */
    public function lottery_db(array $date)
    {

        if(Taskman::query()->count() == 0) {

            $task = new Taskman();
            $task->event = 'jackpot_weekly';
            $task->data = getdate()['wday'];

            if($date['wday'] == (int)1) {
                $task->status = 1;
            } else {
                $task->status = 0;
            }

            $task->save();
        }

        if(Taskman::query()->count() == 0) {

            $task = new Taskman();
            $task->event = 'jackpot_monthly';
            $task->data = getdate()['mday'];

            if($date['mday'] == (int)1) {
                $task->status = 1;
            } else {
                $task->status = 0;
            }

            $task->save();
        }
    }

    /**
     * @param string $type
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @since version
     * @throws Exception
     */
    public function lottery(string $type)
    {

        $date = getdate();

        $dm = $date['mon'];
        $year = $date['year'];
        if($dm == (int)1) {
            $dm = 12;
        } elseif ($dm != (int)1) {
            $dm = $dm - 1;
        }

        $days_in_month = days_in_month($dm, $year);

        if($type === 'week'){

            $summ =  DB::table('name')
                ->where('created_at', '>' ,'(INTERVAL -7 day + CURRENT_DATE())')
                ->get(['money']);
            $sum = 0;

            foreach ($summ as $k => $v) {
                $sum += $v->money;
            }

            $data_from_fake_weekly_jackpot = DB::table('fake_data')->where('type', 2)->first()->amount;
            $summ = ($sum + $data_from_fake_weekly_jackpot) * 0.1;

        } elseif ($type === 'month') {

            $summ =  DB::table('lucky_game_steps')
                ->where('created_at', '>' ,'(INTERVAL -' . $days_in_month . 'day + CURRENT_DATE())')
                ->get(['money']);
            $sum = 0;

            foreach ($summ as $k => $v) {
                $sum += $v->money;
            }

            // DB::table('fake_data')->where('type', 2)->get();
            $data_from_fake_monthly_jackpot = DB::table('fake_data')->where('type', 2)->first()->amount;
            $summ = ($sum + $data_from_fake_monthly_jackpot) * 0.1;

        } else {

            throw new Exception('Incorrect date period');
        }

        $users = LuckyUser::all();
        $usersForLottery = [];
        $lotteryGame = $this->createGame();
        $fund = 0;

        foreach ($users as $k => $v) {
            $fund += $v->balance;

            if (0 < $v->balance) {
                $usersForLottery[] = $v->id;
            }
        }

        $udl = count($usersForLottery);
        $reward = 0;

        $fund = 10000;

        $n = 100;
        if ($udl <= $n) {
            $n = $udl;
        }

        /**
         * @param $array
         *
         * @return array
         *
         * @since version
         */
        function shuffle_assoc(&$array)
        {
            $keys = array_keys($array);

            shuffle($keys);

            $new = [];
            foreach ($keys as $key) {
                $new[$key] = $array[$key];
            }

            return $array = $new;
        }

        $temp_user_db = shuffle_assoc($usersForLottery);
        $new_user_db = [];

        foreach ($temp_user_db as $k => $v) {

            static $limiter = 1;
            if ($limiter <= 100) {
                $new_user_db[] = $v;
            }
            $limiter++;
        }

        $l = count($new_user_db);
        $recipient = [];

        for ($i = 0; $i < $l; $i++) {

            if (0 === $i) {
                $reward = $fund * 0.4;
            } elseif (1 === $i) {
                $reward = $fund * 0.25;
            } elseif (2 === $i) {
                $reward = $fund * 0.10;
            } elseif (3 === $i) {
                $reward = $fund * 0.05;
            } elseif (4 === $i) {
                $reward = $fund * 0.032;
            } elseif (4 < $i && $i <= 9) {
                $reward = $fund * 0.005;
            } elseif (9 < $i && $i <= 24) {
                $reward = $fund * 0.003;
            } elseif (24 < $i && $i <= 49) {
                $reward = $fund * 0.002;
            } elseif (49 < $i && $i <= 99) {
                $reward = $fund * 0.001;
            }

            $recipient[$i] = $reward;
        }

        for ($id = 0; $id < $l; $id++) {

            $userId = $new_user_db[$id];
            $tokens = $recipient[$id];

            $this->reward($userId, $tokens);
            $this->addToLotteryUser($lotteryGame->id, $userId, $tokens);
        }

        return response()->json([
            'response' => $recipient
        ]);

    }

    /**
     * @param $gameId
     * @param $userId
     * @param $tokens
     *
     *
     * @since version
     */
    private function addToLotteryUser($gameId, $userId, $tokens)
    {
        $lottery = new LotteryUser();
        $lottery->game_id = $gameId;
        $lottery->user_id = $userId;
        $lottery->tokens = $tokens;
        $lottery->save();
    }

    /**
     * @param $userId
     * @param $tokens
     *
     *
     * @since version
     */
    private function reward($userId, $tokens)
    {
        $userService = app()->make(UserContract::class);
        $userService->addBalance($userId, $tokens);
    }

    /**
     *
     * @return LotteryGame
     *
     * @since version
     */
    private function createGame()
    {
        $game = new LotteryGame();
        $game->save();

        return $game;
    }
}
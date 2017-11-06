<?php

class LotteryController extends Controller
{
    /**
     * @var LotteryContract
     */
    private $service;

    /**
     * LotteryContract constructor.
     * @param LotteryContract $service
     */
    public function __construct(LotteryContract $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request - schedule for tasks
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @since version
     */
    public function lottery(Request $request)
    {
        $response_w = '';
        $response_m = '';

        if ('f1FyQa' != $request->hash) {

            return response()->json([
                'response' => 'no correct hash'
            ]);

        } else {

            $date = getdate(); //init date array
            $this->service->lottery_db($date);

            $week_stat = Taskman::query(); // get status data from db
            $week_pay_status = $week_stat[0];

            if($date['wday'] == (int)1 && $week_pay_status->status == (int)0) {

                $week_pay_status->status = (int)1;
                $week_pay_status->update();
                $this->service->lottery('week'); // платим

                $response_w = 'week lottery held';

            } elseif ($date['wday'] == (int)1 && $week_pay_status->status == (int)1) {

                $response_w = 'empty task';

            } elseif ($date['wday'] != (int)1 && $week_pay_status->status == (int)1) {

                $week_pay_status->status = (int)0;
                $week_pay_status->update();

                $response_w = 'empty task';

            } elseif ($date['wday'] != (int)1 && $week_pay_status->status == (int)0) {

                $response_w = 'empty task';
            }

            $month_stat = Taskman::query(); // get status data from db
            $month_pay_status = $month_stat[0];

            if($date['mday'] == (int)1 && $month_pay_status->status == (int)0) {

                $month_pay_status->status = (int)1;
                $month_pay_status->update();
                $this->service->lottery('month');

                $response_m = 'month lottery held';

            } elseif ($date['mday'] == (int)1 && $month_pay_status->status == (int)1) {

                $response_m = 'empty task';

            } elseif ($date['mday'] != (int)1 && $month_pay_status->status == (int)1) {

                $month_pay_status->status = (int)0;
                $month_pay_status->update();

                $response_m = 'empty task';

            } elseif ($date['mday'] != (int)1 && $month_pay_status->status == (int)0) {

                $response_m = 'empty task';
            }
        }

        return response()->json([
            'response' => [
                'week'  => $response_w,
                'month' => $response_m,
            ]
        ]);
    }
}

?>

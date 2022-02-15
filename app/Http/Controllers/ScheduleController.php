<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use DatePeriod;
use DateInterval;

class ScheduleController extends Controller
{
  public function createShift(Request $request, Store $store, Branch $branch)
  {
    $validated = $request->validate([
      "name" => "required|string",
      "start_time" => "required|date_format:H:i",
      "end_time" => "required|date_format:H:i",
    ]);

    $success = DB::table('shifts')->insert(
      [
        "name" => $validated["name"],
        "start_time" => $validated["start_time"],
        "end_time" => $validated["end_time"],
        "branch_id" => $branch->id,
        "store_id" => $store->id,
      ]
    );

    if ($success) {
      return response()->json([
        "message" => "Shift created",
        "status" => "success"
      ], 200);
    } else {
      return response()->json([
        "message" => "Shift is not created",
        "status" => "failure"
      ], 200);
    }
  }

  public function createSchedule(Request $request, Store $store, Branch $branch)
  {
    $validated = $request->validate([
      "employee_id" => "required|numeric",
      "shift_id" => "required|numeric",
      "start_date" => "required|date_format:Y-m-d",
      "end_date" => "required|date_format:Y-m-d",
      "week_day" => "required|string",
    ]);

    // 0 -> 6: Sun Mon -> ...
    $date_list = [];
    $week_day = explode(",", $validated['week_day']);

    // get all Week days date in the period
    foreach ($week_day as $day) {
      $date_list = array_merge($date_list, $this->getDateForSpecificDayBetweenDates($validated["start_date"], $validated["end_date"],  $day));
    }

    // sort date
    usort($date_list, function ($time1, $time2) {
      if (strtotime($time1) > strtotime($time2))
        return 1;
      else if (strtotime($time1) > strtotime($time2))
        return -1;
      else
        return 0;
    });


    $schedules = [];

    foreach ($date_list as $date) {
      array_push($schedules, [
        'employee_id' => $validated['employee_id'],
        'shift_id' => $validated['shift_id'],
        'date' => $date,
        'status' => 0,
        'branch_id' => $branch->id,
        'store_id' => $store->id,
      ]);
    }

    $success = DB::table('schedules')->insert($schedules);

    return response()->json([
      'message' =>  $success,
      'data' => $schedules
    ], 200);
  }


  public function getSchedule(Request $request, Store $store, Branch $branch)
  {
    $shifts = $branch->shifts;

    $selected_date = $request->query('selected_date');

    $mode = $request->query('mode');

    $from_date = $to_date = '';

    if ($mode === "day") {
      $from_date = $to_date = $selected_date;
    } else if ($mode === "month") {
      $from_date = date('Y-m-01',  strtotime($selected_date));
      $to_date = date('Y-m-t', strtotime($selected_date));
    } else {
      $from_date = date("Y-m-d", strtotime('monday this week', strtotime($selected_date)));
      $to_date = date("Y-m-d", strtotime('sunday this week', strtotime($selected_date)));
    }

    $data = [];
    foreach ($shifts as $shift) {

      $schedules = $shift->schedules()
        ->where('schedules.date', '>=', $from_date)
        ->where('schedules.date', '<=', $to_date)
        ->join('employees', 'employees.id', '=', 'schedules.employee_id')
        ->select('schedules.*', 'employees.name as employee_name', 'employees.img_url as employee_img_url', 'employees.phone as employee_phone')
        ->get()->toArray();

      $schedules = array_map(function ($v) {
        return [
          'employee_id' => $v['employee_id'],
          'employee_name' => $v['employee_name'],
          'status' => $v['status'],
          'date' => date("d/m/Y", strtotime($v['date'])),
          'employee_img_url' => $v['employee_img_url'],
          'employee_phone' => $v['employee_phone'],
          'schedule_id' => $v['id']
        ];
      }, $schedules);

      array_push($data, array_merge(
        $shift->toArray(),
        ['schedules' => $schedules]
      ));
    }

    return response()->json([
      'data' => $data,
      'mode' => $mode,
      'selected_date' => $selected_date,
    ]);
  }

  // this is used for booking a shift for an employee form
  public function getEmpAndShiftOfBranch(Request $request, Store $store, Branch $branch)
  {
    $shifts = $branch->shifts;
    $employees = DB::table('employee_work_branch')
      ->where('employee_work_branch.branch_id', $branch->id)
      ->leftJoin('employees', 'employees.id', '=', 'employee_work_branch.employee_id')
      ->where('employees.status', 'active')
      ->get();

    return response()->json([
      'data' => [
        'shifts' => $shifts,
        'employees' => $employees,
      ]
    ]);
  }

  private function getDateForSpecificDayBetweenDates($startDate, $endDate, $day_number)
  {
    $endDate = strtotime($endDate);
    $days = array('1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '0' => 'Sunday');
    for ($i = strtotime($days[$day_number], strtotime($startDate)); $i <= $endDate; $i = strtotime('+1 week', $i))
      $date_array[] = date('Y-m-d', $i);

    return $date_array;
  }


  public function checkAttendance(Request $request, Store $store, Branch $branch)
  {
    $validated = $request->validate([
      "data" => "required|array"
    ]);

    foreach ($validated["data"] as $schedule) {
      DB::table('schedules')->where('id', $schedule['schedule_id'])->update(['status' => $schedule['status']]);
    }

    return response()->json([
      'message' => 'updated schedule'
    ]);
  }
}

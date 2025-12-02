<?php
// includes/time_slots.php

// Define the standard time slots for the institution
$time_slots = [
    '08:10 - 08:50',
    '08:50 - 09:30',
    '09:40 - 10:20',
    '10:20 - 11:00',
    '11:10 - 11:50',
    '11:50 - 12:30',
    '13:00 - 13:40',
    '13:40 - 14:20',
    '14:30 - 15:10',
    '15:10 - 15:50',
    '16:00 - 16:40',
    '16:40 - 17:20',
    '17:30 - 18:10',
    '18:10 - 18:50',
    '19:00 - 19:35',
    '19:35 - 20:10',
    '20:15 - 20:50',
    '20:50 - 21:25',
    '21:30 - 22:05',
    '22:05 - 22:40'
];

// Helper function to get the start time from a slot string (e.g., "08:10")
function get_slot_start($slot)
{
    return explode(' - ', $slot)[0];
}

// Helper function to get the end time from a slot string
function get_slot_end($slot)
{
    return explode(' - ', $slot)[1];
}

// Helper function to calculate end time based on start slot and number of blocks
function get_end_time_from_blocks($start_slot, $num_blocks)
{
    global $time_slots;
    $start_index = array_search($start_slot, $time_slots);

    if ($start_index === false)
        return null;

    // Calculate the index of the last slot in the block sequence
    // e.g., start at index 0, 2 blocks -> covers index 0 and 1. End time is end of index 1.
    $end_index = $start_index + $num_blocks - 1;

    if (isset($time_slots[$end_index])) {
        return get_slot_end($time_slots[$end_index]);
    }

    return null; // Out of bounds
}
?>
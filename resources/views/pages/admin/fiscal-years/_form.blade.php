<div class="grid gap-5 sm:grid-cols-2">
    <x-form.input name="name" label="Fiscal year name" :value="$fiscalYear->name" placeholder="FY 2026" required />
    <div></div>
    <x-form.input name="starts_on" label="Starts on" type="date" :value="$fiscalYear->starts_on?->format('Y-m-d')" required />
    <x-form.input name="ends_on" label="Ends on" type="date" :value="$fiscalYear->ends_on?->format('Y-m-d')" required />
    <x-form.input name="attendance_threshold_days" label="Attendance threshold (days)" type="number" min="1" :value="$fiscalYear->attendance_threshold_days ?? 90" required help="Present days required before the attendance claim is enabled." />
    <x-form.input name="attendance_credit_points" label="Attendance credit points" type="number" min="0.01" step="0.01" :value="$fiscalYear->attendance_credit_points ?? 10" required />
</div>

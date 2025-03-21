<script setup>
import { ref, watch, computed } from "vue";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch"; // Add this import
import { Label } from "@/components/ui/label"; // Add this import
import { useForm } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { addDays, format, startOfWeek } from "date-fns";
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogFooter,
} from "@/components/ui/dialog";

const props = defineProps({
	schedules: Array,
});

const days = [
	"Monday",
	"Tuesday",
	"Wednesday",
	"Thursday",
	"Friday",
	"Saturday",
	"Sunday",
];

const hours = Array.from({ length: 24 }, (_, i) => ({
	value: i.toString().padStart(2, "0"),
	label: new Date(0, 0, 0, i).toLocaleString("en-US", { hour: "numeric", hour12: true }),
}));

const minutes = ["00", "15", "30", "45"];

const selectedDays = ref([]);
const defaultStartHour = ref("");
const defaultStartMinute = ref("");
const defaultEndHour = ref("");
const defaultEndMinute = ref("");
const timeError = ref("");

const bulkForm = useForm({
	schedules: [],
});

const validateBulkSchedules = () => {
	timeError.value = "";

	if (
		!defaultStartHour.value ||
		!defaultStartMinute.value ||
		!defaultEndHour.value ||
		!defaultEndMinute.value
	) {
		return false;
	}

	const start =
		parseInt(defaultStartHour.value) * 60 + parseInt(defaultStartMinute.value);
	const end = parseInt(defaultEndHour.value) * 60 + parseInt(defaultEndMinute.value);

	if (end <= start) {
		timeError.value = "End time must be later than start time";
		return false;
	}

	return true;
};

const isBulkFormValid = computed(() => {
	return validateBulkSchedules() && selectedDays.value.length > 0;
});

const handleBulkSubmit = () => {
	if (!validateBulkSchedules() || selectedDays.value.length === 0) {
		return;
	}

	bulkForm.schedules = selectedDays.value.map((day) => ({
		day_of_week: day,
		start_time: `${defaultStartHour.value}:${defaultStartMinute.value}`,
		end_time: `${defaultEndHour.value}:${defaultEndMinute.value}`,
	}));

	bulkForm.post(route("lender.pickup-schedules.store-bulk"), {
		preserveScroll: true,
		onSuccess: () => {
			selectedDays.value = [];
			defaultStartHour.value = "";
			defaultStartMinute.value = "";
			defaultEndHour.value = "";
			defaultEndMinute.value = "";
		},
	});
};

const deleteForm = useForm({});

const scheduleToDelete = ref(null);
const showDeleteDialog = ref(false);

// Add delete type tracking
const deleteType = ref(null); // 'day' or 'timeslot'

const initiateDelete = (schedule, type = "timeslot") => {
	scheduleToDelete.value = schedule;
	deleteType.value = type;
	showDeleteDialog.value = true;
};

const handleDelete = () => {
	if (!scheduleToDelete.value) return;

	if (deleteType.value === "day") {
		// Delete all schedules for this day
		const daySchedules = schedulesGroupedByDay.value[scheduleToDelete.value.day_of_week];
		const deletePromises = daySchedules.map((schedule) =>
			deleteForm.delete(route("lender.pickup-schedules.destroy", schedule.id))
		);

		Promise.all(deletePromises).then(() => {
			showDeleteDialog.value = false;
			scheduleToDelete.value = null;
			deleteType.value = null;
		});
	} else {
		// Delete single time slot
		deleteForm.delete(
			route("lender.pickup-schedules.destroy", scheduleToDelete.value.id),
			{
				preserveScroll: true,
				onSuccess: () => {
					showDeleteDialog.value = false;
					scheduleToDelete.value = null;
					deleteType.value = null;
				},
			}
		);
	}
};

const editingTimeSlot = ref(null);
const editForm = useForm({
	start_time: "",
	end_time: "",
});

const editingStartHour = ref("");
const editingStartMinute = ref("");
const editingEndHour = ref("");
const editingEndMinute = ref("");

const startEditing = (schedule) => {
	editingTimeSlot.value = schedule;

	const [startHour, startMin] = schedule.start_time.split(":");
	const [endHour, endMin] = schedule.end_time.split(":");

	editingStartHour.value = startHour;
	editingStartMinute.value = startMin;
	editingEndHour.value = endHour;
	editingEndMinute.value = endMin;

	editForm.start_time = schedule.start_time;
	editForm.end_time = schedule.end_time;
};

const cancelEditing = () => {
	editingTimeSlot.value = null;
	editForm.reset();
	editingStartHour.value = "";
	editingStartMinute.value = "";
	editingEndHour.value = "";
	editingEndMinute.value = "";
	editTimeError.value = "";
};

watch([editingStartHour, editingStartMinute], () => {
	if (editingStartHour.value && editingStartMinute.value) {
		editForm.start_time = `${editingStartHour.value}:${editingStartMinute.value}`;
	}
});

watch([editingEndHour, editingEndMinute], () => {
	if (editingEndHour.value && editingEndMinute.value) {
		editForm.end_time = `${editingEndHour.value}:${editingEndMinute.value}`;
	}
});

const editTimeError = ref("");

const validateEditTimeRange = () => {
	editTimeError.value = "";

	if (
		!editingStartHour.value ||
		!editingStartMinute.value ||
		!editingEndHour.value ||
		!editingEndMinute.value
	) {
		return false;
	}

	const start =
		parseInt(editingStartHour.value) * 60 + parseInt(editingStartMinute.value);
	const end = parseInt(editingEndHour.value) * 60 + parseInt(editingEndMinute.value);

	if (end <= start) {
		editTimeError.value =
			"End time must be later than start time and within the same day";
		return false;
	}

	return true;
};

watch([editingStartHour, editingStartMinute, editingEndHour, editingEndMinute], () => {
	if (
		editingStartHour.value &&
		editingStartMinute.value &&
		editingEndHour.value &&
		editingEndMinute.value
	) {
		validateEditTimeRange();
	}
});

const isEditFormValid = computed(() => {
	if (
		!editingStartHour.value ||
		!editingStartMinute.value ||
		!editingEndHour.value ||
		!editingEndMinute.value
	) {
		return false;
	}

	const start =
		parseInt(editingStartHour.value) * 60 + parseInt(editingStartMinute.value);
	const end = parseInt(editingEndHour.value) * 60 + parseInt(editingEndMinute.value);

	return end > start && !editTimeError.value;
});

const handleUpdate = () => {
	if (!validateEditTimeRange()) return;

	editForm.start_time = `${editingStartHour.value}:${editingStartMinute.value}`;
	editForm.end_time = `${editingEndHour.value}:${editingEndMinute.value}`;

	// Keep the same day_of_week when updating
	editForm.day_of_week = editingTimeSlot.value.day_of_week;

	editForm.patch(route("lender.pickup-schedules.update", editingTimeSlot.value.id), {
		preserveScroll: true,
		onSuccess: () => {
			cancelEditing();
		},
	});
};

const getNextOccurrence = (dayOfWeek) => {
	const today = new Date();
	today.setHours(0, 0, 0, 0);

	const startOfCurrentWeek = startOfWeek(today, { weekStartsOn: 1 });
	const daysMap = {
		Monday: 0,
		Tuesday: 1,
		Wednesday: 2,
		Thursday: 3,
		Friday: 4,
		Saturday: 5,
		Sunday: 6,
	};

	const dayIndex = daysMap[dayOfWeek];
	const nextDate = addDays(startOfCurrentWeek, dayIndex);

	if (nextDate < today) {
		return addDays(nextDate, 7);
	}

	return nextDate;
};

const formatScheduleTime = (schedule) => {
	const formatTimeString = (timeStr) => {
		const [hours, minutes] = timeStr.split(":");
		const date = new Date();
		date.setHours(parseInt(hours), parseInt(minutes));
		return date.toLocaleTimeString("en-US", {
			hour: "numeric",
			minute: "2-digit",
			hour12: true,
		});
	};

	return `${formatTimeString(schedule.start_time)} to ${formatTimeString(
		schedule.end_time
	)}`;
};

const currentWeekSchedules = computed(() => {
	const today = new Date();
	today.setHours(0, 0, 0, 0);
	// Include all schedules without filtering by is_active
	const schedules = props.schedules || [];

	return [...schedules]
		.filter((schedule) => {
			const nextDate = getNextOccurrence(schedule.day_of_week);
			return isThisWeek(nextDate);
		})
		.sort((a, b) => {
			const dateA = getNextOccurrence(a.day_of_week);
			const dateB = getNextOccurrence(b.day_of_week);
			return dateA - dateB;
		});
});

const nextWeekSchedules = computed(() => {
	const today = new Date();
	today.setHours(0, 0, 0, 0);
	// Include all schedules without filtering by is_active
	const schedules = props.schedules || [];

	return [...schedules]
		.filter((schedule) => {
			const nextDate = getNextOccurrence(schedule.day_of_week);
			return !isThisWeek(nextDate);
		})
		.sort((a, b) => {
			const dateA = getNextOccurrence(a.day_of_week);
			const dateB = getNextOccurrence(b.day_of_week);
			return dateA - dateB;
		});
});

const isThisWeek = (date) => {
	const today = new Date();
	const weekStart = startOfWeek(today, { weekStartsOn: 1 });
	const endOfWeek = addDays(weekStart, 6);
	return date >= weekStart && date <= endOfWeek;
};

// Add new form for adding time slots
const addTimeSlotForm = useForm({
	start_time: "",
	end_time: "",
});

const selectedDay = ref(null);
const showAddTimeSlotDialog = ref(false);

const timeSlotsByDay = computed(() => {
	const grouped = {};
	props.schedules?.forEach((schedule) => {
		if (!grouped[schedule.day_of_week]) {
			grouped[schedule.day_of_week] = [];
		}
		grouped[schedule.day_of_week].push(schedule);
	});

	// Sort each day's schedules by start time
	Object.keys(grouped).forEach((day) => {
		grouped[day].sort((a, b) => {
			return a.start_time.localeCompare(b.start_time);
		});
	});

	return grouped;
});

const handleAddTimeSlot = (day) => {
	selectedDay.value = day;
	showAddTimeSlotDialog.value = true;

	// Reset form
	addStartHour.value = "";
	addStartMinute.value = "";
	addEndHour.value = "";
	addEndMinute.value = "";
	addTimeError.value = "";
};

const submitNewTimeSlot = () => {
	if (!validateAddTimeRange()) return;

	addTimeSlotForm.post(
		route("lender.pickup-schedules.add-time-slot", selectedDay.value),
		{
			preserveScroll: true,
			onSuccess: () => {
				showAddTimeSlotDialog.value = false;
				selectedDay.value = null;
				addTimeSlotForm.reset();
				addStartHour.value = "";
				addStartMinute.value = "";
				addEndHour.value = "";
				addEndMinute.value = "";
			},
		}
	);
};

const toggleForm = useForm({});

const toggleScheduleActive = (schedule) => {
	toggleForm.patch(
		route("lender.pickup-schedules.toggle", schedule.id),
		{},
		{
			preserveScroll: true,
			preserveState: true,
		}
	);
};

const schedulesGroupedByDay = computed(() => {
	const grouped = {};
	// Make sure we're including all schedules regardless of is_active status
	props.schedules?.forEach((schedule) => {
		if (!grouped[schedule.day_of_week]) {
			grouped[schedule.day_of_week] = [];
		}
		grouped[schedule.day_of_week].push(schedule);
	});

	// Sort time slots within each day
	Object.keys(grouped).forEach((day) => {
		grouped[day].sort((a, b) => a.start_time.localeCompare(b.start_time));
	});

	return grouped;
});

// Sort days in correct order
const sortedDays = computed(() => {
	const dayOrder = {
		Monday: 1,
		Tuesday: 2,
		Wednesday: 3,
		Thursday: 4,
		Friday: 5,
		Saturday: 6,
		Sunday: 7,
	};
	return Object.keys(schedulesGroupedByDay.value).sort(
		(a, b) => dayOrder[a] - dayOrder[b]
	);
});

// Update week sorting logic
const isInCurrentWeek = (dayOfWeek) => {
	const today = new Date();
	const todayDayIndex = today.getDay() || 7; // Convert Sunday (0) to 7
	const dayIndex = daysMap[dayOfWeek];

	return dayIndex >= todayDayIndex;
};

const daysMap = {
	Monday: 1,
	Tuesday: 2,
	Wednesday: 3,
	Thursday: 4,
	Friday: 5,
	Saturday: 6,
	Sunday: 7,
};

// Update schedule grouping computeds
const currentWeekDays = computed(() =>
	sortedDays.value.filter((day) => isInCurrentWeek(day))
);

const nextWeekDays = computed(() =>
	sortedDays.value.filter((day) => !isInCurrentWeek(day))
);

const addStartHour = ref("");
const addStartMinute = ref("");
const addEndHour = ref("");
const addEndMinute = ref("");
const addTimeError = ref("");

// Add watchers for add time slot form
watch([addStartHour, addStartMinute], () => {
	if (addStartHour.value && addStartMinute.value) {
		addTimeSlotForm.start_time = `${addStartHour.value}:${addStartMinute.value}`;
	}
});

watch([addEndHour, addEndMinute], () => {
	if (addEndHour.value && addEndMinute.value) {
		addTimeSlotForm.end_time = `${addEndHour.value}:${addEndMinute.value}`;
	}
});

// Add validation for add time slot
const validateAddTimeRange = () => {
	addTimeError.value = "";

	if (
		!addStartHour.value ||
		!addStartMinute.value ||
		!addEndHour.value ||
		!addEndMinute.value
	) {
		return false;
	}

	const start = parseInt(addStartHour.value) * 60 + parseInt(addStartMinute.value);
	const end = parseInt(addEndHour.value) * 60 + parseInt(addEndMinute.value);

	if (end <= start) {
		addTimeError.value = "End time must be later than start time";
		return false;
	}

	return true;
};

const isAddFormValid = computed(() => {
	if (
		!addStartHour.value ||
		!addStartMinute.value ||
		!addEndHour.value ||
		!addEndMinute.value
	) {
		return false;
	}
	return validateAddTimeRange();
});
</script>

<template>
	<div class="flex flex-col h-full">
		<!-- Title section -->
		<div class="sticky top-0 z-10 bg-background border-b px-6 py-4">
			<h2 class="text-lg font-semibold">Pickup Schedule Management</h2>
			<p class="text-muted-foreground text-sm">
				Set your regular availability for item handovers
			</p>
		</div>

		<!-- Scrollable content -->
		<div class="flex-1 overflow-y-auto px-6 py-6">
			<div class="space-y-6 max-w-3xl mx-auto">
				<!-- Add Availability Section -->
				<div class="space-y-5 rounded-lg border p-5">
					<!-- Day Selection -->
					<div>
						<h3 class="mb-2 text-base font-semibold">Select Days</h3>
						<div class="flex flex-wrap gap-1.5">
							<Button
								v-for="day in days"
								:key="day"
								variant="outline"
								size="sm"
								:class="{
									'bg-primary text-primary-foreground': selectedDays.includes(day),
								}"
								@click="
									selectedDays.includes(day)
										? (selectedDays = selectedDays.filter((d) => d !== day))
										: selectedDays.push(day)
								"
							>
								{{ day }}
							</Button>
						</div>
					</div>

					<!-- Time Range -->
					<div class="grid sm:grid-cols-2 gap-4">
						<!-- Start Time -->
						<div>
							<label class="text-sm font-medium mb-2 block">From</label>
							<div class="grid grid-cols-2 gap-2">
								<Select v-model="defaultStartHour">
									<SelectTrigger>
										<SelectValue placeholder="Hour" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem
											v-for="hour in hours"
											:key="hour.value"
											:value="hour.value"
										>
											{{ hour.label }}
										</SelectItem>
									</SelectContent>
								</Select>

								<Select v-model="defaultStartMinute">
									<SelectTrigger>
										<SelectValue placeholder="Min" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem v-for="min in minutes" :key="min" :value="min">
											{{ min }}
										</SelectItem>
									</SelectContent>
								</Select>
							</div>
						</div>

						<!-- End Time -->
						<div>
							<label class="text-sm font-medium mb-2 block">To</label>
							<div class="grid grid-cols-2 gap-2">
								<Select v-model="defaultEndHour">
									<SelectTrigger>
										<SelectValue placeholder="Hour" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem
											v-for="hour in hours"
											:key="hour.value"
											:value="hour.value"
										>
											{{ hour.label }}
										</SelectItem>
									</SelectContent>
								</Select>

								<Select v-model="defaultEndMinute">
									<SelectTrigger>
										<SelectValue placeholder="Min" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem v-for="min in minutes" :key="min" :value="min">
											{{ min }}
										</SelectItem>
									</SelectContent>
								</Select>
							</div>
						</div>
					</div>

					<p v-if="timeError" class="text-destructive text-sm">
						{{ timeError }}
					</p>

					<Button
						class="w-full"
						:disabled="!isBulkFormValid || bulkForm.processing"
						@click="handleBulkSubmit"
					>
						Create Schedules
					</Button>
				</div>

				<!-- Current Availability Section -->
				<div class="space-y-5">
					<div class="flex items-center justify-between">
						<h3 class="text-base font-semibold">Current Availability</h3>
					</div>

					<!-- Current Week Section -->
					<div v-if="currentWeekDays.length" class="space-y-5">
						<h4 class="text-sm font-medium text-muted-foreground">This Week</h4>
						<div
							v-for="day in currentWeekDays"
							:key="day"
							class="rounded-lg border overflow-hidden"
						>
							<!-- Day Header -->
							<div
								class="bg-muted/50 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
							>
								<div>
									<h4 class="font-medium">{{ day }}</h4>
									<p class="text-xs text-muted-foreground">
										{{ format(getNextOccurrence(day), "MMM d, yyyy") }}
									</p>
								</div>
								<div class="flex gap-2">
									<Button size="sm" @click="handleAddTimeSlot(day)">Add Time</Button>
									<Button
										size="sm"
										variant="destructive"
										@click="initiateDelete(schedulesGroupedByDay[day][0], 'day')"
									>
										Remove Day
									</Button>
								</div>
							</div>

							<!-- Time Slots -->
							<div class="divide-y">
								<div
									v-for="schedule in schedulesGroupedByDay[day]"
									:key="schedule.id"
									class="px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
									:class="[!schedule.is_active && 'bg-destructive/5']"
								>
									<div class="grid">
										<label
											:for="`schedule-active-${schedule.id}`"
											class="flex items-center gap-3 cursor-pointer"
										>
											<Switch
												:id="`schedule-active-${schedule.id}`"
												:model-value="schedule.is_active"
												:disabled="toggleForm.processing"
												@update:model-value="toggleScheduleActive(schedule)"
											/>
											<div>
												<span
													class="font-medium text-sm"
													:class="!schedule.is_active && 'text-muted-foreground'"
												>
													{{ formatScheduleTime(schedule) }}
												</span>
											</div>
										</label>
										<div class="pl-[52px]">
											<span
												class="text-xs"
												:class="
													schedule.is_active
														? 'text-muted-foreground'
														: 'text-destructive'
												"
											>
												{{
													schedule.is_active
														? "Available for bookings"
														: "Currently unavailable"
												}}
											</span>
										</div>
									</div>

									<div class="flex items-center gap-2 pl-[52px] sm:pl-0">
										<Button size="sm" variant="outline" @click="startEditing(schedule)">
											Edit
										</Button>
										<Button size="sm" variant="outline" @click="initiateDelete(schedule)">
											Remove
										</Button>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Next Week Section -->
					<div v-if="nextWeekDays.length" class="space-y-5">
						<h4 class="text-sm font-medium text-muted-foreground">Next Week</h4>
						<div
							v-for="day in nextWeekDays"
							:key="day"
							class="rounded-lg border overflow-hidden"
						>
							<!-- Day Header -->
							<div
								class="bg-muted/50 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
							>
								<div>
									<h4 class="font-medium">{{ day }}</h4>
									<p class="text-xs text-muted-foreground">
										{{ format(getNextOccurrence(day), "MMM d, yyyy") }}
									</p>
								</div>
								<div class="flex gap-2">
									<Button size="sm" @click="handleAddTimeSlot(day)">Add Time</Button>
									<Button
										size="sm"
										variant="destructive"
										@click="initiateDelete(schedulesGroupedByDay[day][0], 'day')"
									>
										Remove Day
									</Button>
								</div>
							</div>

							<!-- Time Slots -->
							<div class="divide-y">
								<div
									v-for="schedule in schedulesGroupedByDay[day]"
									:key="schedule.id"
									class="px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
									:class="[!schedule.is_active && 'bg-destructive/5']"
								>
									<div class="grid">
										<label
											:for="`schedule-active-${schedule.id}`"
											class="flex items-center gap-3 cursor-pointer"
										>
											<Switch
												:id="`schedule-active-${schedule.id}`"
												:model-value="schedule.is_active"
												:disabled="toggleForm.processing"
												@update:model-value="toggleScheduleActive(schedule)"
											/>
											<div>
												<span
													class="font-medium text-sm"
													:class="!schedule.is_active && 'text-muted-foreground'"
												>
													{{ formatScheduleTime(schedule) }}
												</span>
											</div>
										</label>
										<div class="pl-[52px]">
											<span
												class="text-xs"
												:class="
													schedule.is_active
														? 'text-muted-foreground'
														: 'text-destructive'
												"
											>
												{{
													schedule.is_active
														? "Available for bookings"
														: "Currently unavailable"
												}}
											</span>
										</div>
									</div>

									<div class="flex items-center gap-2 pl-[52px] sm:pl-0">
										<Button size="sm" variant="outline" @click="startEditing(schedule)">
											Edit
										</Button>
										<Button size="sm" variant="outline" @click="initiateDelete(schedule)">
											Remove
										</Button>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- No schedules message -->
					<p
						v-if="!Object.keys(schedulesGroupedByDay).length"
						class="text-muted-foreground py-8 text-sm text-center"
					>
						No schedules set yet. Add your availability using the form above.
					</p>
				</div>
			</div>
		</div>
	</div>

	<ConfirmDialog
		:show="showDeleteDialog"
		:title="deleteType === 'day' ? 'Delete Day Schedule' : 'Delete Time Slot'"
		:description="
			deleteType === 'day'
				? `Are you sure you want to delete all time slots for ${scheduleToDelete?.day_of_week}? This action cannot be undone.`
				: 'Are you sure you want to delete this time slot? This action cannot be undone.'
		"
		confirmLabel="Delete"
		confirmVariant="destructive"
		:processing="deleteForm.processing"
		@confirm="handleDelete"
		@update:show="showDeleteDialog = $event"
		@cancel="
			() => {
				showDeleteDialog = false;
				deleteType = null;
				scheduleToDelete = null;
			}
		"
	/>

	<!-- Add Time Slot Dialog -->
	<Dialog :open="showAddTimeSlotDialog" @update:open="showAddTimeSlotDialog = false">
		<DialogContent
			class="w-[calc(100vw-2rem)] sm:max-w-[425px] p-0 flex flex-col max-h-[calc(100vh-2rem)] overflow-hidden rounded-lg"
		>
			<DialogHeader class="sm:p-6 p-4">
				<DialogTitle>Add Time Slot for {{ selectedDay }}</DialogTitle>
			</DialogHeader>

			<div class="sm:px-6 px-4 flex-1">
				<div class="grid gap-4">
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<!-- Start Time -->
						<div class="space-y-2">
							<label class="text-sm font-medium">From</label>
							<div class="grid grid-cols-2 gap-2">
								<Select v-model="addStartHour">
									<SelectTrigger class="h-10">
										<SelectValue placeholder="Hour" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem
											v-for="hour in hours"
											:key="hour.value"
											:value="hour.value"
										>
											{{ hour.label }}
										</SelectItem>
									</SelectContent>
								</Select>

								<Select v-model="addStartMinute">
									<SelectTrigger class="h-10">
										<SelectValue placeholder="Min" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem v-for="min in minutes" :key="min" :value="min">
											{{ min }}
										</SelectItem>
									</SelectContent>
								</Select>
							</div>
						</div>

						<!-- End Time -->
						<div class="space-y-2">
							<label class="text-sm font-medium">To</label>
							<div class="grid grid-cols-2 gap-2">
								<Select v-model="addEndHour">
									<SelectTrigger class="h-10">
										<SelectValue placeholder="Hour" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem
											v-for="hour in hours"
											:key="hour.value"
											:value="hour.value"
										>
											{{ hour.label }}
										</SelectItem>
									</SelectContent>
								</Select>

								<Select v-model="addEndMinute">
									<SelectTrigger class="h-10">
										<SelectValue placeholder="Min" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem v-for="min in minutes" :key="min" :value="min">
											{{ min }}
										</SelectItem>
									</SelectContent>
								</Select>
							</div>
						</div>
					</div>

					<p v-if="addTimeError" class="text-destructive text-sm">
						{{ addTimeError }}
					</p>
				</div>
			</div>

			<DialogFooter class="sm:p-6 p-4">
				<div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
					<Button
						class="sm:w-auto w-full"
						variant="outline"
						@click="showAddTimeSlotDialog = false"
					>
						Cancel
					</Button>
					<Button
						class="sm:w-auto w-full"
						@click="submitNewTimeSlot"
						:disabled="!isAddFormValid || addTimeSlotForm.processing"
					>
						Add Time Slot
					</Button>
				</div>
			</DialogFooter>
		</DialogContent>
	</Dialog>

	<!-- Time Slot Edit Dialog -->
	<Dialog
		:open="editingTimeSlot !== null"
		@update:open="(open) => !open && cancelEditing()"
	>
		<DialogContent
			class="w-[calc(100vw-2rem)] sm:max-w-[425px] p-0 flex flex-col max-h-[calc(100vh-2rem)] overflow-hidden rounded-lg"
		>
			<DialogHeader class="sm:p-6 p-4">
				<DialogTitle>Edit Time Slot</DialogTitle>
			</DialogHeader>

			<div class="sm:px-6 px-4 flex-1">
				<div class="grid gap-4">
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<!-- Start Time -->
						<div class="space-y-2">
							<label class="text-sm font-medium">From</label>
							<div class="grid grid-cols-2 gap-2">
								<Select v-model="editingStartHour">
									<SelectTrigger class="h-10">
										<SelectValue placeholder="Hour" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem
											v-for="hour in hours"
											:key="hour.value"
											:value="hour.value"
										>
											{{ hour.label }}
										</SelectItem>
									</SelectContent>
								</Select>

								<Select v-model="editingStartMinute">
									<SelectTrigger class="h-10">
										<SelectValue placeholder="Min" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem v-for="min in minutes" :key="min" :value="min">
											{{ min }}
										</SelectItem>
									</SelectContent>
								</Select>
							</div>
						</div>

						<!-- End Time -->
						<div class="space-y-2">
							<label class="text-sm font-medium">To</label>
							<div class="grid grid-cols-2 gap-2">
								<Select v-model="editingEndHour">
									<SelectTrigger class="h-10">
										<SelectValue placeholder="Hour" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem
											v-for="hour in hours"
											:key="hour.value"
											:value="hour.value"
										>
											{{ hour.label }}
										</SelectItem>
									</SelectContent>
								</Select>

								<Select v-model="editingEndMinute">
									<SelectTrigger class="h-10">
										<SelectValue placeholder="Min" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem v-for="min in minutes" :key="min" :value="min">
											{{ min }}
										</SelectItem>
									</SelectContent>
								</Select>
							</div>
						</div>
					</div>

					<p v-if="editTimeError" class="text-destructive text-sm">
						{{ editTimeError }}
					</p>
				</div>
			</div>

			<DialogFooter class="sm:p-6 p-4">
				<div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
					<Button class="sm:w-auto w-full" variant="outline" @click="cancelEditing">
						Cancel
					</Button>
					<Button
						class="sm:w-auto w-full"
						@click="handleUpdate"
						:disabled="!isEditFormValid || editForm.processing"
					>
						Save Changes
					</Button>
				</div>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>

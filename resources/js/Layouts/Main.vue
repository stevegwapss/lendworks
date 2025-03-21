<script setup>
import { Button } from "@/components/ui/button";
import { SunMoon, Search } from "lucide-vue-next";
import Sidebar from "../Components/Sidebar.vue";
import MobileSidebar from "../Components/MobileSidebar.vue";
import Notifications from "../Components/Notifications.vue";
import UserDropdownMenu from "../Components/UserDropdownMenu.vue";
import { switchTheme } from "../theme";
import { useForm, router } from "@inertiajs/vue3";
import InputField from "@/Components/InputField.vue";
import Footer from "@/Components/footer.vue";

// use route.params() to 'stack' search query parameters coming from different components and pass them as one parameter

const params = route().params;
// searchTerm is automatically passed from controller->view->layout

const props = defineProps({ searchTerm: String });

const form = useForm({
	search: props.searchTerm,
});

const search = () => {
	router.get(route("explore"), {
		search: form.search,
	});
};
</script>

<template>
	<!-- layout wrapper -->
	<div class="grid min-h-screen w-full md:grid-cols-[220px_1fr] lg:grid-cols-[240px_1fr]">
		<!-- main sidebar -->
		<Sidebar />

		<!-- main content (1fr)-->
		<div class="flex flex-col">
			<!-- header -->
			<header class="bg-background sticky top-0 z-10 border-b">
				<div
					class="mx-auto flex h-14 max-w-screen-2xl items-center gap-4 px-6 lg:h-[60px]"
				>
					<!-- mobile sidebar -->
					<MobileSidebar />

					<!-- search bar -->
					<div class="flex-1">
						<form @submit.prevent="search">
							<div class="relative">
								<Search
									class="absolute left-2.5 top-3 h-4 w-4 text-muted-foreground z-10"
								/>
								<InputField
									type="search"
									placeholder="Search anything..."
									bg="bg-muted"
									addedClass="m-0 py-2 w-full pl-8 appearance-none bg-muted md:w-2/3 lg:w-1/3 h-10"
									v-model="form.search"
								/>
							</div>
						</form>
					</div>

					<!-- header actions -->
					<div class="flex items-center gap-1">
						<!-- theme toggle -->
						<Button @click="switchTheme()" variant="ghost" size="icon">
							<SunMoon class="h-9 w-9" />
							<span class="sr-only">Toggle dark mode</span>
						</Button>

						<!-- notifications - only show for logged in users -->
						<Notifications v-if="$page.props.auth.user" />

						<!-- user dropdown menu -->
						<UserDropdownMenu />
					</div>
				</div>
			</header>

			<!-- main content -->
			<main class="lg:max-w-screen-2xl lg:p-8 flex-1 w-full p-6 mx-auto">
				<!-- slot content -->
				<slot />
			</main>

			<!-- footer -->
			<Footer />
		</div>
	</div>
</template>

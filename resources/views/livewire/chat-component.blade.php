<div class="flex flex-col bg-gray-900 text-gray-200 p-4 rounded-lg">
    <!-- Todo List -->
    <div class="mt-4 bg-gray-800 rounded-lg p-3">
        <h3 class="text-lg font-medium mb-2">Your Todos</h3>
        <div class="space-y-2">
            @foreach($todos as $index => $todo)
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        wire:click="toggleTodo({{ $todo['id'] }})"
                        @checked($todo['completed'])
                        class="rounded text-indigo-600 focus:ring-indigo-500"
                    >
                    <span class="text-sm {{ $todo['completed'] ? 'line-through text-gray-500' : '' }}">
                        {{ $todo['title'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>

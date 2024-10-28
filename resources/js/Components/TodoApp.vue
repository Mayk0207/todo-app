<template>
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold my-4">Todo List</h1>

        <input v-model="searchQuery" class="border p-2 w-full my-4" placeholder="Search todos">
        <div class="flex gap-1 my-1">
            <select v-model="selectedFilterGroup" class="border p-1">
                <option value="">All</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
            </select>
        </div>
        <br>
        <div class="flex gap-2 my-2">
            <input v-model="newTodo" class="border p-2 w-full" placeholder="Add new todo" @keyup.enter="addTodo">
            <select v-model="selectedGroup" class="border p-2">
                <option disabled value="">Select ...</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
            </select>
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 border border-blue-700 rounded" @click="addTodo">Add</button>
        </div>

        <p v-if="errorMessage" class="text-red-500">{{ errorMessage }}</p>

        <div v-if="isEditing" class="my-4 border p-4 rounded">
            <h2 class="text-lg font-semibold">Edit Todo</h2>
            <input v-model="editedTodo.title" class="border p-2 w-full my-2" placeholder="Edit todo">
            <select v-model="editedTodo.group_id" class="border p-2 w-full my-2">
                <option disabled value="">Select group...</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
            </select>
            <button class="bg-green-500 text-white p-2 mr-1 font-bold py-2 px-4 border border-blue-700 rounded" @click="updateTodo">Update</button>
            <button class="bg-gray-500 text-white p-2 font-bold py-2 px-4 border border-blue-700 rounded" @click="cancelEdit">Cancel</button>
        </div>

        <p v-if="editErrorMessage" class="text-red-500">{{ editErrorMessage }}</p>

        <ul>
            <li v-for="todo in filteredTodos" :key="todo.id" class="flex items-center gap-2">
                <input type="checkbox" v-model="todo.completed" @change="updateTodoStatus(todo)">
                <span :class="{ 'line-through': todo.completed }">{{ todo.title }}</span>
                <span class="ml-2 text-gray-500">({{ todo.groupName }})</span>
                <button class="ml-auto bg-yellow-500 text-white p-1 mb-2 font-bold py-2 px-4 border border-blue-700 rounded" @click="editTodo(todo)">Edit</button>
                <button class="ml-1 bg-red-500 text-white p-1 mb-2 font-bold py-2 px-4 border border-blue-700 rounded" @click="deleteTodo(todo.id)">Delete</button>
            </li>
        </ul>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            todos: [],
            newTodo: '',
            selectedGroup: null,
            selectedFilterGroup: '', 
            groups: [],
            searchQuery: '',
            isEditing: false,
            errorMessage: '',     
            editErrorMessage: '', 
            editedTodo: {
                id: null,
                title: '',
                group_id: null
            }
        };
    },
    computed: {
        filteredTodos() {
            return this.todos.filter(todo => {
                const matchesSearchQuery = todo.title.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesGroupFilter = !this.selectedFilterGroup || todo.group_id === this.selectedFilterGroup;
                return matchesSearchQuery && matchesGroupFilter;
            });
        }
    },
    methods: {
        async fetchTodos() {
            const response = await axios.get('/api/todos');
            this.todos = response.data.map(todo => ({
                ...todo,
                groupName: this.groups.find(group => group.id === todo.group_id)?.name 
            }));
        },
        async fetchGroups() {
            const response = await axios.get('/api/groups');
            this.groups = response.data;
        },
        async addTodo() {
    if (this.newTodo === '' || !this.selectedGroup) {
        this.errorMessage = 'Title and Category are required to add a todo.';
        return;
    }

    const trimmedTitle = this.newTodo.trim().toLowerCase(); 

    const duplicateTodo = this.todos.find(todo => 
        todo.title.trim().toLowerCase() === trimmedTitle && 
        todo.group_id === this.selectedGroup 
    );

    console.log('Checking for duplicates...');
    console.log('New Todo Title:', trimmedTitle);
    console.log('Selected Group:', this.selectedGroup);
    console.log('Existing Todos:', this.todos.map(todo => ({ title: todo.title.trim(), group_id: todo.group_id })));

    if (duplicateTodo) {
        this.errorMessage = 'This todo with the same title and category already exists. Duplicate todos are not allowed.';
        console.log('Duplicate found:', duplicateTodo);
        return; 
    }

    try {
        this.errorMessage = '';

        const response = await axios.post('/api/todos', { 
            title: trimmedTitle, 
            group_id: this.selectedGroup 
        });

        const newTodo = {
            ...response.data,
            groupName: this.groups.find(group => group.id === this.selectedGroup)?.name
        };

        this.todos.push(newTodo); 
        this.newTodo = ''; 
        this.selectedGroup = null; 
    } catch (error) {
        console.error('Error adding todo:', error.response ? error.response.data : error.message);
    }
},

        editTodo(todo) {
            this.editedTodo = { id: todo.id, title: todo.title, group_id: todo.group_id };
            this.isEditing = true;
            this.selectedGroup = todo.group_id; 
        },
        async updateTodo() {
            if (this.editedTodo.title === '' || !this.editedTodo.group_id) {
                this.editErrorMessage = 'Title and Group are required to update the todo.';
                return;
            }

            try {
                this.editErrorMessage = '';
                await axios.put(`/api/todos/${this.editedTodo.id}`, {
                    title: this.editedTodo.title,
                    group_id: this.editedTodo.group_id
                });

                const index = this.todos.findIndex(todo => todo.id === this.editedTodo.id);
                if (index !== -1) {
                    this.todos[index].title = this.editedTodo.title;
                    this.todos[index].group_id = this.editedTodo.group_id;
                    this.todos[index].groupName = this.groups.find(group => group.id === this.editedTodo.group_id)?.name;
                }

                this.cancelEdit();
            } catch (error) {
                console.error('Error updating todo:', error.response ? error.response.data : error.message);
            }
        },
        cancelEdit() {
            this.isEditing = false;
            this.editedTodo = { id: null, title: '', group_id: null };
            this.selectedGroup = null;
        },
        async deleteTodo(id) {
            await axios.delete(`/api/todos/${id}`);
            this.todos = this.todos.filter(todo => todo.id !== id);
        },
        async updateTodoStatus(todo) {
            try {
                await axios.patch(`/api/todos/${todo.id}`, { completed: todo.completed });
            } catch (error) {
                console.error('Error updating todo status:', error.response ? error.response.data : error.message);
            }
        }
    },
    mounted() {
        this.fetchGroups().then(() => {
            this.fetchTodos();
        });
    }
};
</script>

<style scoped>
.line-through {
    text-decoration: line-through;
}
</style>

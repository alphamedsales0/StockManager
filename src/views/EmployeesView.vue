<template>

<v-container fluid class="pa-6">


<!-- HEADER -->

<v-card
class="mb-6 rounded-xl"
elevation="3"
>


<v-card-title
class="d-flex align-center justify-space-between pa-6"
>


<div class="d-flex align-center">


<v-avatar
color="primary"
size="52"
class="mr-4"
>


<v-icon size="30">
mdi-account-group
</v-icon>


</v-avatar>



<div>


<h1 class="text-h5 font-weight-bold">

Mitarbeiter

</h1>


<p class="text-body-2 text-medium-emphasis mb-0">

Personalverwaltung

</p>


</div>


</div>





<v-btn

color="primary"

variant="flat"

size="large"

prepend-icon="mdi-account-plus"

@click="$router.push('/employees/add')"

>

Neuer Mitarbeiter

</v-btn>



</v-card-title>


</v-card>









<!-- KPI CARDS -->


<v-row class="mb-6">


<v-col
cols="12"
sm="4"
>


<v-card

class="rounded-xl"

elevation="2"

>


<v-card-text
class="d-flex align-center"
>


<v-avatar
color="primary"
size="48"
class="mr-4"
>


<v-icon>

mdi-account-multiple

</v-icon>


</v-avatar>



<div>


<div class="text-caption">

Gesamt Mitarbeiter

</div>



<div class="text-h5 font-weight-bold">

{{ employees.length }}

</div>


</div>



</v-card-text>


</v-card>


</v-col>









<v-col
cols="12"
sm="4"
>


<v-card

class="rounded-xl"

elevation="2"

>


<v-card-text
class="d-flex align-center"
>


<v-avatar
color="success"
size="48"
class="mr-4"
>


<v-icon>

mdi-account-check

</v-icon>


</v-avatar>



<div>


<div class="text-caption">

Aktiv

</div>



<div class="text-h5 font-weight-bold">

{{ activeEmployees }}

</div>


</div>



</v-card-text>


</v-card>


</v-col>









<v-col
cols="12"
sm="4"
>


<v-card

class="rounded-xl"

elevation="2"

>


<v-card-text
class="d-flex align-center"
>


<v-avatar
color="warning"
size="48"
class="mr-4"
>


<v-icon>

mdi-office-building

</v-icon>


</v-avatar>



<div>


<div class="text-caption">

Abteilungen

</div>



<div class="text-h5 font-weight-bold">

{{ departmentsCount }}

</div>


</div>



</v-card-text>


</v-card>


</v-col>



</v-row>









<!-- FILTER BAR -->


<v-card

class="mb-6 rounded-xl"

elevation="2"

>


<v-card-text>


<v-row align="center">





<v-col
cols="12"
md="5"
>


<v-text-field


v-model="search"


label="Suche Mitarbeiter"

variant="outlined"

density="comfortable"

prepend-inner-icon="mdi-magnify"

clearable


/>




</v-col>







<v-col
cols="12"
md="3"
>



<v-select


v-model="selectedDepartment"


label="Abteilung"


variant="outlined"


density="comfortable"


prepend-inner-icon="mdi-office-building"


:items="departments"


clearable



/>



</v-col>









<v-col
cols="12"
md="3"
>



<v-select


v-model="selectedPosition"


label="Position"


variant="outlined"


density="comfortable"


prepend-inner-icon="mdi-account-tie"


:items="positions"


clearable



/>



</v-col>







<v-col
cols="12"
md="1"
class="text-center"
>


<v-btn

icon

variant="text"

@click="loadEmployees"

>


<v-icon>

mdi-refresh

</v-icon>


</v-btn>



</v-col>





</v-row>



</v-card-text>


</v-card>









<!-- TABLE -->


<v-card

class="rounded-xl"

elevation="3"

>



<v-data-table


:headers="headers"


:items="filteredEmployees"


:loading="loading"


items-per-page="10"



>



<!-- AVATAR -->

<template
#item.photo="{item}"
>


<v-avatar
size="42"
color="grey-lighten-3"
>


<img

v-if="item.photo"

:src="item.photo"

style="
width:100%;
height:100%;
object-fit:cover;
"

/>


<v-icon
v-else
>

mdi-account

</v-icon>


</v-avatar>



</template>









<!-- NAME -->


<template
#item.name="{item}"
>


<div>


<div class="font-weight-bold">

{{item.vorname}}
{{item.nachname}}

</div>


<div class="text-caption text-medium-emphasis">

{{item.email}}

</div>


</div>


</template>









<!-- STATUS -->


<template
#item.status="{item}"
>


<v-chip

:color="item.is_active ? 'success':'error'"

size="small"

>


{{

item.is_active
?
'Aktiv'
:
"Inaktiv"

}}


</v-chip>


</template>








<!-- ACTIONS -->


<template
#item.actions="{item}"
>


<v-btn

icon

variant="text"

color="primary"

@click="openDetail(item.id)"

>


<v-icon>

mdi-eye

</v-icon>


</v-btn>





<v-btn

icon

variant="text"

color="warning"

@click="editEmployee(item.id)"

>


<v-icon>

mdi-pencil

</v-icon>


</v-btn>



</template>





</v-data-table>


</v-card>







<!-- SNACKBAR -->

<v-snackbar

v-model="snackbar"

:color="snackbarColor"

location="bottom right"

timeout="3000"

>


{{ snackbarText }}



<template #actions>


<v-btn

variant="text"

@click="snackbar=false"

>

OK

</v-btn>


</template>



</v-snackbar>





</v-container>


</template>

<script setup>

import {
    ref,
    computed,
    onMounted
} from 'vue'


import {
    useRouter
} from 'vue-router'


import axios from 'axios'



const router = useRouter()





// =======================
// STATE
// =======================


const employees = ref([])


const loading = ref(false)


const search = ref('')


const selectedDepartment = ref(null)


const selectedPosition = ref(null)





// Snackbar

const snackbar = ref(false)

const snackbarText = ref('')

const snackbarColor = ref('success')








// =======================
// TABLE HEADERS
// =======================


const headers = [

{
title:'Foto',
key:'photo',
sortable:false
},

{
title:'Mitarbeiter',
key:'name'
},

{
title:'Nummer',
key:'mitarbeiter_nummer'
},

{
title:'Abteilung',
key:'abteilung'
},

{
title:'Position',
key:'position'
},

{
title:'Telefon',
key:'telefon'
},

{
title:'Status',
key:'status'
},

{
title:'Aktionen',
key:'actions',
sortable:false
}

]









// =======================
// LOAD EMPLOYEES
// =======================


const loadEmployees = async()=>{


loading.value=true



try{


const response =
await axios.get(
'/api/employees_list.php'
)



if(response.data.success){


employees.value =
response.data.employees



}

else{


throw new Error(
response.data.error
)


}




}

catch(error){


console.error(error)



snackbarText.value =
'Fehler beim Laden der Mitarbeiter'


snackbarColor.value =
'error'


snackbar.value=true



}


finally{


loading.value=false


}



}








// =======================
// FILTER LISTS
// =======================


const departments = computed(()=>{


return [

...new Set(

employees.value.map(

e=>e.abteilung

)

.filter(Boolean)

)

]


})






const positions = computed(()=>{


return [

...new Set(

employees.value.map(

e=>e.position

)

.filter(Boolean)

)

]


})









// =======================
// SEARCH + FILTER
// =======================


const filteredEmployees = computed(()=>{


return employees.value.filter(employee=>{


const text =

`${employee.vorname}
${employee.nachname}
${employee.email}
${employee.mitarbeiter_nummer}`
.toLowerCase()



const matchesSearch =

!search.value ||

text.includes(

search.value.toLowerCase()

)






const matchesDepartment =

!selectedDepartment.value ||

employee.abteilung ===
selectedDepartment.value





const matchesPosition =

!selectedPosition.value ||

employee.position ===
selectedPosition.value





return (

matchesSearch &&

matchesDepartment &&

matchesPosition

)



})


})









// =======================
// KPI
// =======================


const activeEmployees = computed(()=>{


return employees.value.filter(

e=>e.is_active

).length



})







const departmentsCount = computed(()=>{


return departments.value.length


})









// =======================
// NAVIGATION
// =======================


const openDetail=(id)=>{


router.push(
`/employees/${id}`
)


}







const editEmployee=(id)=>{


router.push(

`/employees/edit/${id}`

)


}









// INIT


onMounted(()=>{


loadEmployees()



})



</script>







<style scoped>


.v-card {

transition:
transform .25s ease,
box-shadow .25s ease;

}



.v-card:hover {

transform:translateY(-2px);

}



.v-card-title {

font-weight:600;

letter-spacing:.3px;

}



.v-btn {

text-transform:none;

font-weight:600;

}



.v-data-table {

border-radius:18px;

}



</style>
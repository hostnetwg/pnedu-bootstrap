@foreach($archivedCourses as $course)
    @include('courses.partials.archived-course-card', ['course' => $course])
@endforeach

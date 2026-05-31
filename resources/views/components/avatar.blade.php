@props(['url', 'name', 'class' => 'h-full w-full object-cover'])

<img src="{{ $url }}" 
     alt="{{ $name }}" 
     onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name={{ urlencode($name) }}&color=4F46E5&background=E0E7FF';"
     class="{{ $class }}">

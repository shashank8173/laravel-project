{{--
  Usage:
  @include('partials.page-header', [
      'title' => 'Employee',
      'breadcrumbs' => [
          ['label' => 'Dashboard', 'url' => route('dashboard.admin')],
          ['label' => 'Employee'],
      ],
      'actions' => '<a href="..." class="btn add-btn">Add</a>',
  ])
--}}
@php
    $title = $title ?? ($__env->yieldContent('heading') ?: 'Page');
    $breadcrumbs = $breadcrumbs ?? [];
    $actions = $actions ?? null;
@endphp
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title">{{ $title }}</h3>
            @if(count($breadcrumbs))
                <ul class="breadcrumb">
                    @foreach($breadcrumbs as $crumb)
                        <li class="breadcrumb-item {{ empty($crumb['url']) ? 'active' : '' }}">
                            @if(!empty($crumb['url']))
                                <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                            @else
                                {{ $crumb['label'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        @if(!empty($actions))
            <div class="col-auto float-end ms-auto">
                {!! $actions !!}
            </div>
        @endif
    </div>
</div>

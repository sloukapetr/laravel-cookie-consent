<?php
return [
    'title' => 'We use cookies',
    'intro' => 'This website uses cookies in order to enhance the overall user experience.',
    'link' => 'Take a look at our <a href=":url">Cookies Policy</a> for more information.',
    'customize_intro' => 'Select the categories you consent to.',

    'essentials' => 'Only essentials',
    'all' => 'Accept all',
    'customize' => 'Customize',
    'manage' => 'Manage cookies',
    'details' => [
        'more' => 'More details',
        'less' => 'Less details',
    ],
    'save' => 'Save settings',
    'cookie' => 'Cookie',
    'purpose' => 'Purpose',
    'duration' => 'Duration',
    'year' => 'Year|Years',
    'day' => 'Day|Days',
    'hour' => 'Hour|Hours',
    'minute' => 'Minute|Minutes',

    'categories' => [
        'essentials' => [
            'title' => 'Essential cookies',
            'description' => 'There are some cookies that we have to include in order for certain web pages to function. For this reason, they do not require your consent.',
        ],
        'analytics' => [
            'title' => 'Analytics cookies',
            'description' => 'We use these for internal research on how we can improve the service we provide for all our users. These cookies assess how you interact with our website.',
        ],
        'optional' => [
            'title' => 'Optional cookies',
            'description' => 'These cookies enable features that could improve your user experience, but their absence will not impact your ability to browse our website.',
        ],
        'marketing' => [
            'title' => 'Marketing cookies',
            'description' => 'These cookies are used to measure the performance of our campaigns and to display advertising that is relevant to you, here and on other websites.',
        ],
    ],

    'defaults' => [
        'consent' => 'Used to store the user\'s cookie consent preferences.',
        'session' => 'Used to identify the user\'s browsing session.',
        'csrf' => 'Used to secure both the user and our website against cross-site request forgery attacks.',
        '_ga' => 'Main cookie used by Google Analytics, enables a service to distinguish one visitor from another.',
        '_ga_ID' => 'Used by Google Analytics to persist session state.',
        '_gid' => 'Used by Google Analytics to identify the user.',
        '_gat' => 'Used by Google Analytics to throttle the request rate.',
        '_fbp' => 'Used by Meta (Facebook) to identify browsers for advertising measurement and targeting purposes.',
        '_fbc' => 'Stores the last Meta (Facebook) ad click that brought you to our website.',
        '_hjSessionUser' => 'Used by Hotjar to recognize the same visitor across visits.',
        '_hjSession' => 'Used by Hotjar to hold the data of the current visitor session.',
        '_hjFirstSeen' => 'Used by Hotjar to determine whether this is the visitor\'s first session.',
        '_hjAbsoluteSessionInProgress' => 'Used by Hotjar to measure the first page view of a session.',
        'sklik' => 'Used by Seznam Sklik for retargeting and advertising campaign measurement.',
    ],
];

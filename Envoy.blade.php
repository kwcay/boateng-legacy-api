@setup

    # Load .env file.
    require __DIR__.'/vendor/autoload.php';
    (new \Dotenv\Dotenv(__DIR__, '.env'))->load();

    # Setup variables.
    $gitHost            = env('ENVOY_GIT_HOST', 'deployer');
    $repository         = 'git@'.$gitHost.':doraboateng/api.git';
    $baseDir            = env('ENVOY_BASE_DIR', '/var/www/apps');
    $releasesDir        = "{$baseDir}/releases";
    $liveDir            = env('ENVOY_LIVE_DIR', '/var/www/live');
    $newReleaseName     = date('Ymd-His');
    $localDir           = dirname(__FILE__);

    $productionServer   = env('ENVOY_PRODUCTION_SERVER', '127.0.0.1');
    $localServer        = env('ENVOY_LOCAL_SERVER', '127.0.0.1');

@endsetup



{{-- Servers --}}

@servers(['local' => $localServer, 'production' => $productionServer])



{{-- Zero downtime deployment --}}

{{-- Credits: https://serversforhackers.com/video/deploying-with-envoy-cast --}}
{{-- Credits: https://dyrynda.com.au/blog/an-envoyer-like-deployment-script-using-envoy --}}
{{-- Credits: https://murze.be/2015/11/zero-downtime-deployments-with-envoy --}}



{{-- Deployment pipeline --}}

@story('deploy')

    check-prod
    unit-tests
    git-clone
    setup-app
    composer-install
    update-permissions
    update-symlinks
    optimize
    purge-releases

@endstory

@story('deploy-migrate', ['on' => 'production'])

    git-clone
    setup-app
    composer-install
    update-permissions
    update-symlinks
    down
    migrate
    up
    optimize
    purge-releases

@endstory

@task('deploy-code', ['on' => 'production'])

    cd {{ $liveDir }} && git pull origin master

@endtask

@task('unit-tests', ['on' => 'local'])

    {{ App\Utilities\Cli::green('Running unit tests...') }}
    {{ App\Utilities\Cli::yellow('To do: run tests from Envoy and quit on fail') }}

@endtask

@task('check-prod', ['on' => 'production'])

    {{ App\Utilities\Cli::green('Checking production server...') }}
    ssh -T git@<?= $gitHost ?>

@endtask

@task('git-clone', ['on' => 'production'])

    {{ App\Utilities\Cli::green('Cloning git repository...') }}

    # Check if the release directory exists. If it doesn't, create one.
    [ -d {{ $releasesDir }} ] || mkdir -p {{ $releasesDir }};

    # cd into the releases directory.
    cd {{ $releasesDir }};

    # Clone the repository into a new folder.
    git clone --depth 1 {{ $repository }} {{ $newReleaseName }}  &> /dev/null;

    # Configure sparse checkout.
    #cd {{ $newReleaseName }};
    #git config core.sparsecheckout true;
    #echo "*" > .git/info/sparse-checkout;
    #echo "!storage" >> .git/info/sparse-checkout;
    #git read-tree -mu HEAD;

@endtask

@task('setup-app', ['on' => 'production'])

    {{ App\Utilities\Cli::green('Copying environment file...') }}

    # cd into new folder.
    cd {{ $releasesDir }}/{{ $newReleaseName }};

    # Copy .env file
    cp -f ./.env.production ./.env;

@endtask

@task('composer-install', ['on' => 'production'])

    {{ App\Utilities\Cli::green('Installing composer dependencies...') }}

    # cd into new folder.
    cd {{ $releasesDir }}/{{ $newReleaseName }};

    # Install composer dependencies.
    composer install --prefer-dist --no-scripts --no-dev -q -o;

@endtask

@task('update-permissions', ['on' => 'production'])

    {{ App\Utilities\Cli::green('Updating directory owner and permissions...') }}

    # cd into releases folder
    cd {{ $releasesDir }};

    # Update group owner and permissions
    chgrp -R www-data {{ $newReleaseName }};
    chmod -R ug+rwx {{ $newReleaseName }};
    chmod -R 1777 {{ $newReleaseName }}/storage;

@endtask

@task('update-symlinks', ['on' => 'production'])

    {{ App\Utilities\Cli::green('Updating symbolic links...') }}

    # Make sure the persistent storage directory exists.
    #[ -d {{ $baseDir }}/storage ] || mkdir -p {{ $baseDir }}/storage;
    mkdir -p {{ $baseDir }}/storage/app;
    mkdir -p {{ $baseDir }}/storage/backups;
    mkdir -p {{ $baseDir }}/storage/framework/sessions;
    mkdir -p {{ $baseDir }}/storage/framework/views;
    mkdir -p {{ $baseDir }}/storage/logs;

    # Remove the storage directory and replace with persistent data
    rm -rf {{ $releasesDir }}/{{ $newReleaseName }}/storage;
    cd {{ $releasesDir }}/{{ $newReleaseName }};
    ln -nfs {{ $baseDir }}/storage storage;
    # chmod -R 1777 {{ $baseDir }}/storage;

    ln -nfs {{ $releasesDir }}/{{ $newReleaseName }} {{ $liveDir }};
    chgrp -h www-data {{ $liveDir }};

@endtask

@task('optimize', ['on' => 'production'])

    {{ App\Utilities\Cli::green('Optimizing...') }}

    cd {{ $liveDir }};

    # Optimize installation.
    php artisan cache:clear;
    php artisan clear-compiled;
    php artisan optimize;
    php artisan config:cache;

    {{ App\Utilities\Cli::yellow('Todo: cache routes') }}
    # php artisan route:cache;

    # Clear the OPCache
    # sudo service php5-fpm restart
    {{ App\Utilities\Cli::yellow('Todo: clear OPCache') }}

@endtask

@task('purge-releases', ['on' => 'production'])

    {{ App\Utilities\Cli::green('Purging old releases...') }}

    # This will list our releases by modification time and delete all but the 5 most recent.
    purging=$(ls -dt {{ $releasesDir }}/* | tail -n +5);

    if [ "$purging" != "" ]; then
        echo Purging old releases: $purging;
        rm -rf $purging;
    else
        echo "No releases found for purging at this time";
    fi

@endtask

@task('init', ['on' => 'production'])

    # Check apps directory.
    {{ App\Utilities\Cli::yellow('Checking apps directory...') }}
    mkdir -p {{ $baseDir }};
    mkdir -p {{ $releasesDir }};

    # Make sure the persistent storage directory exists.
    mkdir -p {{ $baseDir }}/storage;
    mkdir -p {{ $baseDir }}/storage/app;
    mkdir -p {{ $baseDir }}/storage/framework/cache;
    mkdir -p {{ $baseDir }}/storage/framework/sessions;
    mkdir -p {{ $baseDir }}/storage/framework/views;
    mkdir -p {{ $baseDir }}/storage/logs;

@endtask



{{-- Testing Envoy --}}

@story('test')

    test-local
    # test-prod

@endstory

@task('test-local', ['on' => 'local'])

    {{ App\Utilities\Cli::lightBlue('Testing Envoy on localhost...') }}
    {{ App\Utilities\Cli::black('Black') }}
    {{ App\Utilities\Cli::red('Red') }}
    {{ App\Utilities\Cli::green('Green') }}
    {{ App\Utilities\Cli::brown('Brown') }}
    {{ App\Utilities\Cli::blue('Blue') }}
    {{ App\Utilities\Cli::purple('Purple') }}
    {{ App\Utilities\Cli::cyan('Cyan') }}
    {{ App\Utilities\Cli::lightGray('Light gray') }}
    {{ App\Utilities\Cli::darkGray('Dark gray') }}
    {{ App\Utilities\Cli::lightRed('Light red') }}
    {{ App\Utilities\Cli::yellow('Yellow') }}
    {{ App\Utilities\Cli::lightBlue('Light blue') }}
    {{ App\Utilities\Cli::lightPurple('Light purple') }}
    {{ App\Utilities\Cli::lightCyan('Light cyan') }}

    pwd

@endtask

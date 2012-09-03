task :default do  
  commands = [
    "git pull",
    "rm -Rf app/cache/prod/*",
    "export SYMFONY_ENV=prod && php app/console assets:install web",
    "export SYMFONY_ENV=prod && php app/console cache:warmup"
  ]
  
  run_commands(commands)
end

def run_commands(commands)
  commands.each do|command|
    puts `#{command}`    
  end   
end
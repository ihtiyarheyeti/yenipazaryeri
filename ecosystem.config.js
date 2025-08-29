module.exports = {
  apps: [
    {
      name: "yp-worker",
      script: "cli/worker.php",
      interpreter: "php",
      env: { WORKER_INTERVAL: "3" }
    }
  ]
};

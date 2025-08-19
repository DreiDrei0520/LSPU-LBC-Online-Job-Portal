<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Application Status</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f0f9fa;
    }

    .navbar {
      background: #ffffff;
      padding: 15px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #e1e1e1;
    }

    .navbar img {
      height: 40px;
    }

    .nav-links a {
      margin: 0 15px;
      text-decoration: none;
      color: #666;
      font-weight: 500;
    }

    .nav-links a:hover,
    .nav-links a.active {
      color: #007bff;
      font-weight: 600;
    }

    .nav-icons i {
      font-size: 20px;
      margin-left: 20px;
      color: #888;
    }

    .container {
      padding: 30px;
    }

    .status-box {
      background: #fff;
      border-radius: 8px;
      padding: 25px 30px;
      box-shadow: 0 0 5px rgba(0,0,0,0.05);
      margin-bottom: 30px;
      position: relative;
    }

    .status-box h2 {
      font-size: 20px;
      margin-bottom: 20px;
    }

    .applicant-id {
      position: absolute;
      right: 30px;
      top: 30px;
      font-weight: bold;
      color: #0056b3;
    }

    .timeline {
      position: relative;
      margin-left: 30px;
      border-left: 2px solid #d0d0d0;
      padding-left: 25px;
    }

    .step {
      margin-bottom: 30px;
      position: relative;
    }

    .step::before {
      content: "";
      position: absolute;
      left: -34px;
      top: 3px;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background-color: #ccc;
    }

    .step.completed::before {
      background-color: #4caf50;
      content: "\f00c";
      font-family: "Font Awesome 5 Free";
      font-weight: 900;
      color: white;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .step.active::before {
      background-color: #00aaff;
      content: "\f192";
      font-family: "Font Awesome 5 Free";
      font-weight: 900;
      color: white;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .step-title {
      font-weight: bold;
      font-size: 16px;
    }

    .step-time {
      font-size: 13px;
      color: #888;
      margin-bottom: 5px;
    }

    .step-desc {
      font-size: 14px;
      color: #555;
    }

    .calendar-button {
      margin-top: 10px;
      padding: 5px 12px;
      background: #e6f7ff;
      border: 1px solid #5ac8fa;
      color: #007bff;
      font-size: 13px;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
    }

    .info-box {
      background: #e8f5fb;
      padding: 20px;
      border-radius: 6px;
      font-size: 14px;
      color: #333;
    }

    .info-box ul {
      list-style: none;
      padding-left: 0;
    }

    .info-box li {
      margin-bottom: 10px;
    }

    .info-box i {
      color: #007bff;
      margin-right: 8px;
    }

  </style>
</head>
<body>
  <div class="navbar">
    <img src="logo.png" alt="Logo" />
    <div class="nav-links">
      <a href="#">Home</a>
      <a href="#">Job</a>
      <a href="#" class="active">Applications</a>
      <a href="#">Status</a>
    </div>
    <div class="nav-icons">
      <i class="fas fa-bell"></i>
      <i class="fas fa-user-circle"></i>
    </div>
  </div>

  <div class="container">
    <div class="status-box">
      <h2>Application Status</h2>
      <div class="applicant-id">Applicant ID: <span style="color:#0033cc;">#APP25789</span></div>
      <div class="timeline">
        <div class="step completed">
          <div class="step-title">Application Submitted</div>
          <div class="step-time">Jan 15, 2025 - 9:30AM</div>
          <div class="step-desc">Your application has been successfully submitted and is under review.</div>
        </div>

        <div class="step completed">
          <div class="step-title">Document Verification</div>
          <div class="step-time">Jan 16, 2025 - 02:15PM</div>
          <div class="step-desc">All submitted documents have been verified and approved.</div>
        </div>

        <div class="step active">
          <div class="step-title">Interview Scheduled</div>
          <div class="step-time">Jan 20, 2025 - 10:00AM</div>
          <div class="step-desc">
            Your interview has been scheduled. Please check your email for details.
            <br />
            <button class="calendar-button"><i class="fas fa-calendar-plus"></i> Add to Calendar</button>
          </div>
        </div>

        <div class="step">
          <div class="step-title">Final Decision</div>
          <div class="step-desc">Your application has been successfully submitted and is under review.</div>
        </div>
      </div>
    </div>

    <div class="info-box">
      <ul>
        <li><i class="fas fa-info-circle"></i> Please arrive 15 minutes before your scheduled interview time.</li>
        <li><i class="fas fa-info-circle"></i> Bring original copies of all submitted documents.</li>
        <li><i class="fas fa-info-circle"></i> Check your email regularly for updates and notifications.</li>
      </ul>
    </div>
  </div>
</body>
</html>

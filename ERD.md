# ERD Core

```text
University 1──N Campus
University 1──N Faculty 1──N Department 1──N StudyProgram
StudyProgram 1──N Curriculum N──M Course
AcademicYear 1──N Semester 1──N CourseOffering 1──N ClassSection
ClassSection N──M LecturerProfile; ClassSection 1──N Schedule/Meeting
StudentProfile 1──N StudentEnrollment N──1 StudyProgram
StudentEnrollment 1──N StudyPlan 1──N StudyPlanItem N──1 ClassSection
StudyPlanItem 1──1 StudentGrade; Meeting 1──N AttendanceSession 1──N StudentAttendance
StudentEnrollment 1──N StudentInvoice 1──N InvoiceItem
StudentEnrollment 1──N Payment 1──N PaymentAllocation N──1 StudentInvoice
User N──M Role; User 1──N AuditLog
```

All domain entities use ULID. Junction tables use a composite key where the relation itself is the identity, or a ULID when it carries lifecycle data.

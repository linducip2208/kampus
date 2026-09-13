# Permission matrix

Role tidak hanya menyembunyikan menu. Query dan service harus memeriksa role serta scope pada pivot `role_user`.

| Domain | Read | Mutate | Approve/Publish |
|---|---|---|---|
| Students | BAAK, Dean, Rector, Advisor, Auditor | BAAK | BAAK/Dean |
| Courses & Curriculum | Academic, BAAK, Rector | Academic | Head of Study Program |
| KRS | Student, Advisor, BAAK | Student draft | Advisor |
| Grades | Student, Lecturer, Advisor | Lecturer draft | Academic/Head of Study Program |
| Finance | Finance, Student (own) | Finance | Finance manager |
| Audit | Auditor, Super Admin | System only | Immutable |

Granular naming mengikuti pola `students.view`, `krs.submit`, `krs.approve`, `grades.publish`, `finance.payment.verify`.
